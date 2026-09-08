<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\AccountingBundle\Service;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Enum\PeriodStatus;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Exception\LedgerLockedException;
use Augias\AccountingBundle\Repository\AccountingPeriodRepository;
use Augias\AccountingBundle\Repository\LedgerEntryRepository;
use Augias\CoreBundle\Entity\Company;
use Augias\UserBundle\Entity\User;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use function array_key_first;
use function array_keys;
use function array_map;
use function count;
use function strval;

/**
 * Owns the life of an {@see AccountingPeriod}: bringing one into existence when
 * an entry first needs it, deciding which one a given entry belongs in, and
 * closing it.
 *
 * Closing is the operation everything else here exists to support, and it is
 * one-way. It numbers the period's entries gaplessly, chains them by hash, locks
 * them, and freezes the totals — after which a correction can only be a
 * reversing entry in a later, open period.
 *
 * @see \Augias\AccountingBundle\Tests\Service\AccountingPeriodManagerTest
 */
final readonly class AccountingPeriodManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountingPeriodRepository $periodRepository,
        private LedgerEntryRepository $entryRepository,
        private LedgerEntryHasher $hasher,
    ) {
    }

    /**
     * The period covering a date, created if it does not exist yet.
     *
     * Periods are made lazily rather than generated ahead of time: a company
     * that has booked nothing has no periods, and one that books a payment
     * dated two years back gets that period brought into being on the spot
     * instead of the entry landing nowhere.
     *
     * The new period is persisted but not flushed — the caller is inside a
     * flush of its own more often than not.
     */
    public function periodFor(Company $company, PeriodType $type, DateTimeImmutable $date): AccountingPeriod
    {
        $existing = $this->periodRepository->findForDate($company, $type, $date);

        if ($existing instanceof AccountingPeriod) {
            return $existing;
        }

        $period = new AccountingPeriod()
            ->setType($type)
            ->setYear((int) $date->format('Y'))
            ->setOrdinal($type->ordinalOf($date))
            ->setStartDate($type->startOf($date))
            ->setEndDate($type->endOf($date));

        $period->setCompany($company);

        $this->entityManager->persist($period);

        return $period;
    }

    /**
     * Files an entry into the right period, and says so when that is not the
     * period its own date calls for.
     *
     * A payment can perfectly well arrive dated inside a period that has
     * already been closed — a bank transfer recorded late, an invoice marked
     * paid after the quarter was sealed. The closed period cannot take it, so
     * the entry goes into the earliest period still open and is flagged
     * {@see LedgerEntry::isLateEntry()}. The date stays truthful and the
     * discrepancy stays visible, which is the only honest way to handle it.
     */
    public function assignPeriod(LedgerEntry $entry, PeriodType $type): void
    {
        $company = $entry->getCompany();
        $period = $this->periodFor($company, $type, $entry->getEntryDate());

        if ($period->isOpen()) {
            $entry->setPeriod($period)
                ->setLateEntry(false);

            return;
        }

        // Its own period is sealed. The earliest open one is where a late entry
        // belongs; if every existing period is closed, today's is created — it
        // is open by construction, since closing runs in date order.
        $fallback = $this->periodRepository->findEarliestOpenPeriod($company, $type)
            ?? $this->periodFor($company, $type, new DateTimeImmutable('today'));

        $entry->setPeriod($fallback)
            ->setLateEntry(true);
    }

    /**
     * Seals a period.
     *
     * @throws LedgerLockedException when the period is already closed, or an
     *                               earlier one is still open — closing out of
     *                               order would tear a hole in the numbering
     *                               and break the chain
     */
    public function close(AccountingPeriod $period, ?User $closedBy = null): void
    {
        if ($period->isClosed()) {
            throw LedgerLockedException::forPeriod($period);
        }

        if ($this->periodRepository->hasOpenPeriodBefore($period)) {
            throw LedgerLockedException::earlierPeriodStillOpen($period);
        }

        $company = $period->getCompany();
        $entries = $this->entryRepository->findForPeriod($period);
        $sealedAt = new DateTimeImmutable();
        $lastHash = null;

        // Per book: the numbering and the chain are per book, since the two are
        // separate statutory registers that happen to share a table.
        $nextNumber = [];
        $previousHash = [];

        foreach ($entries as $entry) {
            $book = $entry->getBook()->value;

            if (! isset($nextNumber[$book])) {
                $nextNumber[$book] = $this->entryRepository->highestSequenceNumber($company, $entry->getBook()) + 1;
                $previousHash[$book] = $this->entryRepository
                    ->lastSealedEntry($company, $entry->getBook())
                    ?->getHash();
            }

            $entry->setSequenceNumber($nextNumber[$book]++)
                ->setPreviousHash($previousHash[$book])
                ->setLockedAt($sealedAt);

            $hash = $this->hasher->hash($entry, $previousHash[$book]);
            $entry->setHash($hash);

            $previousHash[$book] = $hash;
            $lastHash = $hash;
        }

        $period->setStatus(PeriodStatus::Closed)
            ->setClosedAt($sealedAt)
            ->setClosedBy($closedBy)
            ->setEntryCount(count($entries))
            ->setClosingHash($lastHash)
            ->setTotals($this->totalsFor($period, $entries));

        $this->entityManager->flush();
    }

    /**
     * The figures frozen onto the period at closing time: revenue split by
     * activity nature, purchases as one total, all in minor units as strings.
     *
     * Computed from the entries in hand rather than re-queried, so what is
     * stored is exactly what was sealed.
     *
     * @param list<LedgerEntry> $entries
     * @return array<string, mixed>
     */
    private function totalsFor(AccountingPeriod $period, array $entries): array
    {
        $revenue = [];
        $purchase = BigInteger::zero();
        $currencies = [];

        foreach ($entries as $entry) {
            $currencies[$entry->getCurrencyCode()] = true;

            if ($entry->getBook() === LedgerBook::Purchase) {
                $purchase = $purchase->plus($entry->getAmount());

                continue;
            }

            $nature = $entry->getActivityNature();
            $nature = $nature instanceof ActivityNature ? $nature->value : 'unspecified';
            $revenue[$nature] = ($revenue[$nature] ?? BigInteger::zero())->plus($entry->getAmount());
        }

        return [
            'currency' => array_key_first($currencies) ?? '',
            // More than one means the totals above are a mix of currencies and
            // cannot be read as a single figure. Recorded rather than resolved:
            // the books say what they say, and a rate invented at closing time
            // would be worse than a flag.
            'currencies' => array_keys($currencies),
            'revenue' => array_map(strval(...), $revenue),
            'purchase' => (string) $purchase,
            'label' => $period->getLabel(),
        ];
    }
}
