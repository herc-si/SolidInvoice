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

namespace Augias\AccountingBundle\Tests\Functional;

use Augias\AccountingBundle\AccountingSettings;
use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Enum\LedgerEntrySource;
use Augias\AccountingBundle\Enum\PeriodStatus;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Exception\LedgerLockedException;
use Augias\AccountingBundle\Listener\Doctrine\LedgerEntryLockListener;
use Augias\AccountingBundle\Listener\Doctrine\LedgerFeedListener;
use Augias\AccountingBundle\Model\ChainVerification;
use Augias\AccountingBundle\Regime\Fr\MicroEntrepriseRegime;
use Augias\AccountingBundle\Repository\LedgerEntryRepository;
use Augias\AccountingBundle\Service\AccountingPeriodManager;
use Augias\AccountingBundle\Service\AccountingProfileProvider;
use Augias\AccountingBundle\Service\LedgerChainVerifier;
use Augias\AccountingBundle\Service\LedgerFeeder;
use Augias\AccountingBundle\Service\LedgerLockDate;
use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\CoreBundle\Entity\Company;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Entity\Line;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Enum\PaymentStatus;
use Augias\SettingsBundle\SystemConfig;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function array_map;
use function uniqid;

/**
 * The books, end to end: a payment recorded anywhere in the application turns
 * into a ledger entry, the entry lands in the right period, and closing that
 * period seals it beyond reach.
 */
#[CoversClass(LedgerFeeder::class)]
#[CoversClass(LedgerFeedListener::class)]
#[CoversClass(AccountingPeriodManager::class)]
#[CoversClass(LedgerChainVerifier::class)]
#[CoversClass(LedgerEntryLockListener::class)]
#[CoversClass(LedgerLockDate::class)]
final class LedgerBookkeepingTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private EntityManagerInterface $entityManager;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $this->entityManager = $entityManager;

        $this->configureRegime();

        // One client for the whole test: the name is unique per company, and
        // every invoice here is for the same counterparty anyway.
        $this->client = ClientFactory::createOne(['name' => 'Johnston PLC', 'currencyCode' => 'EUR']);
    }

    public function testACapturedInvoicePaymentIsBookedIntoTheRevenueBook(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-02-10'));

        $entries = $this->entries();

        self::assertCount(1, $entries);

        $entry = $entries[0];
        self::assertSame(LedgerBook::Revenue, $entry->getBook());
        self::assertSame(LedgerEntrySource::InvoicePayment, $entry->getSource());
        self::assertSame('120000', (string) $entry->getAmount());
        self::assertSame('EUR', $entry->getCurrencyCode());
        // The date the money moved, not the invoice date and not today.
        self::assertSame('2026-02-10', $entry->getEntryDate()->format('Y-m-d'));
        self::assertSame(ActivityNature::ServicesBnc, $entry->getActivityNature());
        self::assertSame('Johnston PLC', $entry->getCounterpartyName());
        self::assertFalse($entry->isLateEntry());
        self::assertNull($entry->getSequenceNumber(), 'An entry in an open period is not numbered yet.');
    }

    /**
     * The period a payment falls in is brought into existence by that payment,
     * rather than generated ahead of time.
     */
    public function testTheEntryLandsInTheQuarterItsDateFallsIn(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-02-10'));

        $period = $this->entries()[0]->getPeriod();

        self::assertInstanceOf(AccountingPeriod::class, $period);
        self::assertSame('2026-Q1', $period->getLabel());
        self::assertSame(PeriodType::Quarter, $period->getType());
        self::assertSame('2026-01-01', $period->getStartDate()->format('Y-m-d'));
        self::assertSame('2026-03-31', $period->getEndDate()->format('Y-m-d'));
        self::assertTrue($period->isOpen());
    }

    /**
     * A payment is flushed more than once as a matter of course — a status
     * change, a note added later, a retried gateway callback. None of those is
     * a second receipt.
     */
    public function testFlushingTheSamePaymentAgainDoesNotBookItTwice(): void
    {
        $payment = $this->capturedPayment(120_000, new DateTimeImmutable('2026-02-10'));

        $payment->setNotes('Paid by transfer, confirmed by phone');
        $this->entityManager->flush();

        self::assertCount(1, $this->entries());
    }

    public function testAPaymentThatIsNotCapturedIsNotBooked(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-02-10'), PaymentStatus::Pending);

        self::assertSame([], $this->entries());
    }

    public function testNothingIsBookedForACompanyThatKeepsNoBooks(): void
    {
        self::getContainer()->get(SystemConfig::class)->set(AccountingSettings::REGIME, '');

        $this->capturedPayment(120_000, new DateTimeImmutable('2026-02-10'));

        self::assertSame([], $this->entries());
    }

    /**
     * A services-only micro-entreprise is not obliged to keep a purchase
     * register, so it is not given one to fill in.
     */
    public function testTheRevenueBookIsTheOnlyOneAServicesBusinessKeeps(): void
    {
        $regime = self::getContainer()->get(MicroEntrepriseRegime::class);
        $profile = self::getContainer()->get(AccountingProfileProvider::class)
            ->forCompany($this->company);

        self::assertSame([LedgerBook::Revenue], $regime->books($profile));
    }

    public function testClosingAPeriodNumbersSealsAndFreezesIt(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-01-15'));
        $this->capturedPayment(80_000, new DateTimeImmutable('2026-02-10'));

        $period = $this->entries()[0]->getPeriod();
        self::assertInstanceOf(AccountingPeriod::class, $period);

        self::getContainer()->get(AccountingPeriodManager::class)->close($period);

        $entries = $this->entries();

        self::assertSame([1, 2], array_map(static fn (LedgerEntry $e): ?int => $e->getSequenceNumber(), $entries));
        self::assertNull($entries[0]->getPreviousHash(), 'The first entry of a book chains onto nothing.');
        self::assertSame($entries[0]->getHash(), $entries[1]->getPreviousHash());

        foreach ($entries as $entry) {
            self::assertTrue($entry->isLocked());
            self::assertFalse($entry->isEditable());
        }

        self::assertSame(PeriodStatus::Closed, $period->getStatus());
        self::assertSame(2, $period->getEntryCount());
        self::assertSame($entries[1]->getHash(), $period->getClosingHash());
        self::assertSame(
            ['services_bnc' => '200000'],
            $period->getTotals()['revenue'] ?? null,
            'The totals are frozen at closing time rather than recomputed on read.',
        );
    }

    public function testASealedEntryCanNoLongerBeChanged(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-01-15'));

        $period = $this->entries()[0]->getPeriod();
        self::assertInstanceOf(AccountingPeriod::class, $period);
        self::getContainer()->get(AccountingPeriodManager::class)->close($period);

        $this->entries()[0]->setLabel('Something else entirely');

        $this->expectException(LedgerLockedException::class);

        $this->entityManager->flush();
    }

    /**
     * The span a seal cannot cover: a period that has ended but that nobody has
     * closed yet, whose figures have often already been declared. The lock date
     * shuts it without sealing it.
     */
    public function testAnEntryInAPeriodShutByTheLockDateCannotBeChanged(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-01-15'));

        self::getContainer()->get(SystemConfig::class)->set(AccountingSettings::LOCK_DATE, '2026-03-31');

        $entry = $this->entries()[0];

        self::assertFalse($entry->isLocked(), 'Nothing has been sealed — this is the lock date alone.');

        $entry->setLabel('Something else entirely');

        $this->expectException(LedgerLockedException::class);

        $this->entityManager->flush();
    }

    public function testAnEntryInAPeriodShutByTheLockDateCannotBeRemoved(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-01-15'));

        self::getContainer()->get(SystemConfig::class)->set(AccountingSettings::LOCK_DATE, '2026-03-31');

        $entry = $this->entries()[0];

        // preRemove fires on remove(), not on the flush that follows it.
        $this->expectException(LedgerLockedException::class);

        $this->entityManager->remove($entry);
    }

    /**
     * Sealing writes the numbers, the hashes and the lock onto entries whose
     * period the lock date already shuts. Refusing that would make a locked
     * book impossible to close — the guard has to let the seal through.
     */
    public function testTheLockDateDoesNotStopAPeriodFromBeingSealed(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-01-15'));

        self::getContainer()->get(SystemConfig::class)->set(AccountingSettings::LOCK_DATE, '2026-03-31');

        $period = $this->entries()[0]->getPeriod();
        self::assertInstanceOf(AccountingPeriod::class, $period);

        self::getContainer()->get(AccountingPeriodManager::class)->close($period);

        self::assertSame(PeriodStatus::Closed, $period->getStatus());
        self::assertTrue($this->entries()[0]->isLocked());
    }

    /**
     * Money that turns up late keeps the date it moved on, but is filed into the
     * period still open — so it stays correctable. Reading the entry's own date
     * rather than its period's would wrongly freeze it.
     */
    public function testALateEntryDatedBeforeTheLockDateStaysCorrectable(): void
    {
        // An earlier quarter, shut, and a payment dated inside it arriving now.
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-01-15'));

        $period = $this->entries()[0]->getPeriod();
        self::assertInstanceOf(AccountingPeriod::class, $period);
        self::getContainer()->get(AccountingPeriodManager::class)->close($period);

        $this->capturedPayment(50_000, new DateTimeImmutable('2026-02-20'));

        $late = $this->entries()[1];

        self::assertTrue($late->isLateEntry());
        self::assertFalse($late->isLocked());

        $late->setLabel('Corrected after the fact');

        $this->entityManager->flush();

        self::assertSame('Corrected after the fact', $this->entries()[1]->getLabel());
    }

    public function testAClosedPeriodCannotBeClosedTwice(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-01-15'));

        $manager = self::getContainer()->get(AccountingPeriodManager::class);
        $period = $this->entries()[0]->getPeriod();
        self::assertInstanceOf(AccountingPeriod::class, $period);

        $manager->close($period);

        $this->expectException(LedgerLockedException::class);

        $manager->close($period);
    }

    /**
     * Closing out of order would leave a hole in the numbering and break the
     * chain, so it is refused rather than silently renumbered.
     */
    public function testAPeriodCannotBeClosedWhileAnEarlierOneIsStillOpen(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-01-15'));
        $this->capturedPayment(80_000, new DateTimeImmutable('2026-05-20'));

        $second = $this->entries()[1]->getPeriod();
        self::assertInstanceOf(AccountingPeriod::class, $second);
        self::assertSame('2026-Q2', $second->getLabel());

        $this->expectException(LedgerLockedException::class);

        self::getContainer()->get(AccountingPeriodManager::class)->close($second);
    }

    /**
     * A payment recorded after its own quarter was sealed cannot go into that
     * quarter. It keeps its true date, lands in the earliest open period, and
     * says so.
     */
    public function testAPaymentDatedInAClosedPeriodIsFiledLateIntoAnOpenOne(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-01-15'));

        $firstQuarter = $this->entries()[0]->getPeriod();
        self::assertInstanceOf(AccountingPeriod::class, $firstQuarter);
        self::getContainer()->get(AccountingPeriodManager::class)->close($firstQuarter);

        // A second quarter has to exist and be open for the late entry to have
        // somewhere to go.
        $this->capturedPayment(50_000, new DateTimeImmutable('2026-04-02'));
        $this->capturedPayment(30_000, new DateTimeImmutable('2026-03-20'));

        $late = $this->entries()[1];

        self::assertSame('2026-03-20', $late->getEntryDate()->format('Y-m-d'), 'The date stays truthful.');
        self::assertTrue($late->isLateEntry());
        self::assertSame('2026-Q2', $late->getPeriod()?->getLabel());
    }

    public function testASealedBookVerifiesAndTamperingWithItDoesNot(): void
    {
        $this->capturedPayment(120_000, new DateTimeImmutable('2026-01-15'));
        $this->capturedPayment(80_000, new DateTimeImmutable('2026-02-10'));

        $period = $this->entries()[0]->getPeriod();
        self::assertInstanceOf(AccountingPeriod::class, $period);
        self::getContainer()->get(AccountingPeriodManager::class)->close($period);

        $verifier = self::getContainer()->get(LedgerChainVerifier::class);

        $verification = $verifier->verify($this->company, LedgerBook::Revenue);
        self::assertTrue($verification->isValid());
        self::assertSame(2, $verification->entriesChecked);

        // Straight to the database, which is the only way a figure in a sealed
        // book can change — the entity listener refuses it through the ORM.
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE accounting_ledger_entries SET amount = 999 WHERE sequence_number = 1',
        );
        $this->entityManager->clear();

        $verification = $verifier->verify($this->company, LedgerBook::Revenue);

        self::assertFalse($verification->isValid());
        self::assertSame(ChainVerification::REASON_ALTERED, $verification->firstFailure()['reason'] ?? null);
        self::assertSame(1, $verification->firstFailure()['sequenceNumber'] ?? null);
    }

    private function configureRegime(): void
    {
        $config = self::getContainer()->get(SystemConfig::class);

        $config->set(AccountingSettings::REGIME, MicroEntrepriseRegime::CODE);
        $config->set(AccountingSettings::PRIMARY_ACTIVITY, ActivityNature::ServicesBnc->value);
        $config->set(AccountingSettings::DECLARATION_PERIODICITY, PeriodType::Quarter->value);
    }

    private function capturedPayment(
        int $amount,
        DateTimeImmutable $completed,
        PaymentStatus $status = PaymentStatus::Captured,
    ): Payment {
        $invoice = $this->invoice();

        $payment = new Payment();
        $payment->setTotalAmount($amount);
        $payment->setCurrencyCode('EUR');
        $payment->setInvoice($invoice);
        $payment->setClient($invoice->getClient());
        $payment->setStatus($status);
        $payment->setCompleted($completed);
        // Re-read: the identity map is cleared between assertions, which
        // leaves the company held by the test case detached.
        $payment->setCompany($this->entityManager->find(Company::class, $this->company->getId()));

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    private function invoice(): Invoice
    {
        $invoice = new Invoice();
        $invoice->setClient($this->entityManager->find(Client::class, $this->client->getId()));
        $invoice->setStatus(InvoiceStatus::Paid);
        $invoice->setInvoiceId('INV-' . uniqid());
        $invoice->setInvoiceDate(new DateTimeImmutable('2026-01-05'));
        $invoice->addLine(new Line()->setDescription('Consulting')->setPrice(120_000)->setQty(1));

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }

    /**
     * @return list<LedgerEntry>
     */
    private function entries(): array
    {
        $this->entityManager->clear();

        return self::getContainer()->get(LedgerEntryRepository::class)->findBy([], ['entryDate' => 'ASC', 'created' => 'ASC']);
    }
}
