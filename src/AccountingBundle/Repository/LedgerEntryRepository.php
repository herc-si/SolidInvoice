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

namespace Augias\AccountingBundle\Repository;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Enum\LedgerEntrySource;
use Augias\CoreBundle\Entity\Company;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function array_column;

/**
 * @extends EntityRepository<LedgerEntry>
 */
class LedgerEntryRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LedgerEntry::class);
    }

    /**
     * The entry already written for a given payment record, if any. This is
     * what makes the automatic feeders idempotent — it is checked before every
     * insert, and the unique index behind it is the backstop for the race.
     *
     * Takes the company explicitly so it also works from a cron command, where
     * the multi-tenancy filter is switched off.
     */
    public function findBySource(
        Company $company,
        LedgerBook $book,
        LedgerEntrySource $source,
        Ulid $sourceId,
    ): ?LedgerEntry {
        return $this->createQueryBuilder('e')
            ->andWhere('e.company = :company')
            ->andWhere('e.book = :book')
            ->andWhere('e.source = :source')
            ->andWhere('e.sourceId = :sourceId')
            ->setParameter('company', $company)
            ->setParameter('book', $book->value)
            ->setParameter('source', $source->value)
            ->setParameter('sourceId', $sourceId, UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Every entry filed into a period, in the order they will be numbered and
     * hashed at closing time.
     *
     * @return list<LedgerEntry>
     */
    public function findForPeriod(AccountingPeriod $period): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.period = :period')
            ->setParameter('period', $period)
            ->orderBy('e.entryDate', 'ASC')
            ->addOrderBy('e.created', 'ASC')
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * The highest sequence number handed out so far in a book, so the next
     * closing can carry on from it without a gap.
     */
    public function highestSequenceNumber(Company $company, LedgerBook $book): int
    {
        $highest = $this->createQueryBuilder('e')
            ->select('MAX(e.sequenceNumber)')
            ->andWhere('e.company = :company')
            ->andWhere('e.book = :book')
            ->setParameter('company', $company)
            ->setParameter('book', $book->value)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $highest ? 0 : (int) $highest;
    }

    /**
     * The last sealed entry of a book — the head its next closing chains onto.
     */
    public function lastSealedEntry(Company $company, LedgerBook $book): ?LedgerEntry
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.company = :company')
            ->andWhere('e.book = :book')
            ->andWhere('e.sequenceNumber IS NOT NULL')
            ->setParameter('company', $company)
            ->setParameter('book', $book->value)
            ->orderBy('e.sequenceNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Every sealed entry of a book in chain order, for
     * {@see \Augias\AccountingBundle\Service\LedgerChainVerifier}.
     *
     * @return list<LedgerEntry>
     */
    public function findSealedInChainOrder(Company $company, LedgerBook $book): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.company = :company')
            ->andWhere('e.book = :book')
            ->andWhere('e.sequenceNumber IS NOT NULL')
            ->setParameter('company', $company)
            ->setParameter('book', $book->value)
            ->orderBy('e.sequenceNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cash-basis turnover between two inclusive dates, grouped by activity
     * nature and currency. Grouping by currency rather than summing across it
     * is deliberate: converting silently would invent an exchange rate the
     * books never saw.
     *
     * @return list<array{nature: string|null, currency: string, total: string}>
     */
    public function sumByActivityNature(
        Company $company,
        LedgerBook $book,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): array {
        /** @var list<array{nature: string|null, currency: string, total: string}> $rows */
        $rows = $this->createQueryBuilder('e')
            ->select('e.activityNature AS nature', 'e.currencyCode AS currency', 'SUM(e.amount) AS total')
            ->andWhere('e.company = :company')
            ->andWhere('e.book = :book')
            ->andWhere('e.entryDate >= :from')
            ->andWhere('e.entryDate <= :to')
            ->setParameter('company', $company)
            ->setParameter('book', $book->value)
            ->setParameter('from', $from->setTime(0, 0))
            ->setParameter('to', $to->setTime(0, 0))
            ->groupBy('e.activityNature')
            ->addGroupBy('e.currencyCode')
            ->getQuery()
            ->getArrayResult();

        return $rows;
    }

    /**
     * Total of a book over an inclusive date range, in one currency.
     */
    public function sumForRange(
        Company $company,
        LedgerBook $book,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        string $currencyCode,
    ): BigInteger {
        $total = $this->createQueryBuilder('e')
            ->select('SUM(e.amount)')
            ->andWhere('e.company = :company')
            ->andWhere('e.book = :book')
            ->andWhere('e.currencyCode = :currency')
            ->andWhere('e.entryDate >= :from')
            ->andWhere('e.entryDate <= :to')
            ->setParameter('company', $company)
            ->setParameter('book', $book->value)
            ->setParameter('currency', $currencyCode)
            ->setParameter('from', $from->setTime(0, 0))
            ->setParameter('to', $to->setTime(0, 0))
            ->getQuery()
            ->getSingleScalarResult();

        return null === $total ? BigInteger::zero() : BigInteger::of((string) $total);
    }

    /**
     * Currencies a company has actually booked in, so the UI can point out a
     * mixed-currency ledger instead of quietly reporting one of them.
     *
     * @return list<string>
     */
    public function distinctCurrencies(Company $company): array
    {
        /** @var list<array{currencyCode: string}> $rows */
        $rows = $this->createQueryBuilder('e')
            ->select('DISTINCT e.currencyCode AS currencyCode')
            ->andWhere('e.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'currencyCode');
    }
}
