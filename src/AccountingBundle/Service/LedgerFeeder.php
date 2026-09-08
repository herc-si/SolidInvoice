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

use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Enum\LedgerEntrySource;
use Augias\AccountingBundle\Enum\SettlementMethod;
use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Regime\RegimeRegistry;
use Augias\AccountingBundle\Repository\LedgerEntryRepository;
use Augias\BillBundle\Entity\BillPayment;
use Augias\CoreBundle\Entity\Company;
use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Enum\PaymentStatus;
use Augias\SettingsBundle\SystemConfig;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;
use function in_array;
use function trim;

/**
 * Writes the statutory books from what the rest of the application already
 * records, so that keeping them costs the user nothing.
 *
 * Cash accounting is what makes this possible: an entry is owed exactly when
 * money moves, and the application already knows when that happened — a
 * captured {@see Payment} on the revenue side, a {@see BillPayment} on the
 * purchase side. Nothing here reads invoices or bills themselves; an unpaid
 * invoice is not turnover under this regime and has no business in the book.
 *
 * Writing is idempotent. Every automatic entry carries the source record's id,
 * and one is written only if there is not one already — with a unique index
 * behind it as the backstop, since a retried payment webhook and a user
 * clicking twice both end up here.
 *
 * @see \Augias\AccountingBundle\Tests\Service\LedgerFeederTest
 */
final readonly class LedgerFeeder
{
    /**
     * A payment counts as turnover once the money is actually in — authorised
     * is not captured, and pending is not money.
     *
     * A refund is deliberately not in this list and is not booked here at all.
     * One automatic entry exists per payment record, which is what the unique
     * index enforces and what makes re-flushing harmless; a refund is a second
     * movement of money against the same record, and the book takes it the way
     * accounting always has — as a reversing entry, entered by hand against the
     * original, so that both the receipt and its reversal stay visible.
     *
     * @var list<PaymentStatus>
     */
    private const array BOOKABLE_STATUSES = [PaymentStatus::Captured];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountingProfileProvider $profileProvider,
        private RegimeRegistry $registry,
        private AccountingPeriodManager $periodManager,
        private LedgerEntryRepository $entryRepository,
        private SystemConfig $systemConfig,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Books a captured invoice payment into the revenue book.
     *
     * Returns null — without complaint — whenever there is nothing to book:
     * the payment is not captured, the company keeps no books, or the entry
     * already exists. This runs on every payment written by the application,
     * most of which belong to companies that never enabled the module.
     */
    public function recordInvoicePayment(Payment $payment): ?LedgerEntry
    {
        $invoice = $payment->getInvoice();

        if (null === $invoice || ! in_array($payment->getStatus(), self::BOOKABLE_STATUSES, true)) {
            return null;
        }

        $company = $invoice->getCompany();
        $profile = $this->books($company, LedgerBook::Revenue);

        if (! $profile instanceof AccountingProfile) {
            return null;
        }

        $id = $payment->getId();

        if (! $id instanceof Ulid) {
            return null;
        }

        $existing = $this->entryRepository
            ->findBySource($company, LedgerBook::Revenue, LedgerEntrySource::InvoicePayment, $id);

        if ($existing instanceof LedgerEntry) {
            return null;
        }

        $money = $payment->getAmount();
        $client = $payment->getClient() ?? $invoice->getClient();

        $entry = new LedgerEntry()
            ->setBook(LedgerBook::Revenue)
            ->setSource(LedgerEntrySource::InvoicePayment)
            ->setSourceId($id)
            // The date the money moved, which for a captured payment is when it
            // completed — not when the invoice was raised, and not today.
            ->setEntryDate($payment->getCompleted() ?? new DateTimeImmutable('today'))
            ->setLabel($this->label('accounting.entry.label.invoice_payment', $company))
            ->setDocumentReference($invoice->getInvoiceId())
            ->setAmount(BigInteger::of($money->getAmount()))
            ->setCurrencyCode($money->getCurrency()->getCode())
            // The company's main activity is only a default: turnover of a
            // second kind has to be re-filed by hand, which is why the field
            // stays editable on an otherwise read-only automatic entry.
            ->setActivityNature($profile->primaryActivity)
            ->setSettlementMethod(SettlementMethod::fromGatewayName($payment->getMethod()?->getGatewayName()));

        $entry->setCompany($company);

        if (null !== $client) {
            $entry->setCounterparty($client);
        }

        if ('' === $entry->getCounterpartyName()) {
            $entry->setCounterpartyName((string) $invoice->getClient()?->getName());
        }

        return $this->persist($entry, $profile);
    }

    /**
     * Books a supplier payment into the purchase register.
     *
     * Only for companies whose regime actually requires that register — a
     * French micro-entrepreneur on services alone is not obliged to keep one,
     * and filling a statutory register they do not have to produce would be
     * inventing an obligation.
     */
    public function recordBillPayment(BillPayment $payment): ?LedgerEntry
    {
        $bill = $payment->getBill();
        $company = $bill->getCompany();
        $profile = $this->books($company, LedgerBook::Purchase);

        if (! $profile instanceof AccountingProfile) {
            return null;
        }

        $id = $payment->getId();

        if (! $id instanceof Ulid) {
            return null;
        }

        $existing = $this->entryRepository
            ->findBySource($company, LedgerBook::Purchase, LedgerEntrySource::BillPayment, $id);

        if ($existing instanceof LedgerEntry) {
            return null;
        }

        $entry = new LedgerEntry()
            ->setBook(LedgerBook::Purchase)
            ->setSource(LedgerEntrySource::BillPayment)
            ->setSourceId($id)
            ->setEntryDate($payment->getPaidDate())
            ->setLabel($this->label('accounting.entry.label.bill_payment', $company))
            ->setDocumentReference($bill->getBillNumber())
            ->setAmount(BigInteger::of((string) $payment->getAmount()))
            ->setCurrencyCode($payment->getCurrencyCode())
            // Purchases carry no activity nature: nothing about them is capped
            // or charged per activity, and inventing one would only make the
            // register harder to read.
            ->setSettlementMethod(SettlementMethod::fromBillPaymentMethod($payment->getMethod()));

        $entry->setCompany($company)
            ->setCounterparty($bill->getSupplier());

        return $this->persist($entry, $profile);
    }

    /**
     * The company's profile when it keeps the given book, null when it does
     * not — which covers both an unconfigured company and a regime that has no
     * such register.
     */
    private function books(Company $company, LedgerBook $book): ?AccountingProfile
    {
        $profile = $this->profileProvider->forCompany($company);
        $regime = $this->registry->forProfile($profile);

        if (null === $regime || ! in_array($book, $regime->books($profile), true)) {
            return null;
        }

        return $profile;
    }

    private function persist(LedgerEntry $entry, AccountingProfile $profile): LedgerEntry
    {
        $this->periodManager->assignPeriod($entry, $profile->declarationPeriodicity);

        $this->entityManager->persist($entry);

        return $entry;
    }

    /**
     * Automatic labels are translated once, here, into the company's own
     * language and stored as plain text — the same treatment the seeded payment
     * methods get. A book is a document the user prints and keeps; resolving
     * its wording at render time would let a change of interface language
     * rewrite entries that were filed years ago.
     */
    private function label(string $key, Company $company): string
    {
        $locale = trim((string) $this->systemConfig->get(SystemConfig::LOCALE_CONFIG_PATH, $company));

        return $this->translator->trans($key, [], null, '' === $locale ? null : $locale);
    }
}
