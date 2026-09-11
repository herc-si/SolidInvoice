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
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Regime\Fr\MicroEntrepriseRegime;
use Augias\AccountingBundle\Repository\LedgerEntryRepository;
use Augias\AccountingBundle\Service\AccountingPeriodManager;
use Augias\AccountingBundle\Service\LedgerFeeder;
use Augias\BillBundle\Entity\Bill;
use Augias\BillBundle\Entity\BillPayment;
use Augias\BillBundle\Enum\BillPaymentMethod;
use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\CoreBundle\Entity\Company;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\SettingsBundle\SystemConfig;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The tax a supplier charged, as it reaches the purchase register.
 *
 * A company that reclaims VAT declares what it paid, so the figure has to
 * travel from the supplier's bill into the books — under the same cash
 * convention as everything else: pay half the bill and half its tax is
 * deductible.
 */
#[CoversClass(LedgerFeeder::class)]
#[CoversClass(AccountingPeriodManager::class)]
#[Group('functional')]
final class PurchaseTaxTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private EntityManagerInterface $entityManager;

    private Client $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $config = self::getContainer()->get(SystemConfig::class);
        $config->set(SystemConfig::CURRENCY_CONFIG_PATH, 'EUR');
        $config->set(AccountingSettings::REGIME, MicroEntrepriseRegime::CODE);
        // Sale of goods, because that is the activity whose regime keeps a
        // purchase register at all.
        $config->set(AccountingSettings::PRIMARY_ACTIVITY, ActivityNature::SaleOfGoods->value);
        $config->set(AccountingSettings::DECLARATION_PERIODICITY, PeriodType::Quarter->value);

        $this->supplier = ClientFactory::createOne(['name' => 'Fournitures SARL', 'currencyCode' => 'EUR']);
    }

    public function testPayingABillInFullDeductsTheWholeTaxItCarried(): void
    {
        $this->payment($this->bill(120_000, 20_000), 120_000);

        $entry = $this->entry();

        self::assertTrue($entry->hasTax());
        self::assertSame('20000', (string) $entry->getTaxAmount());
        self::assertSame('100000', (string) $entry->getNetAmount());
        self::assertSame([], $entry->getTaxBreakdown(), 'Deductible tax is declared as one figure, not per rate.');
    }

    public function testPayingHalfABillDeductsHalfItsTax(): void
    {
        $this->payment($this->bill(120_000, 20_000), 60_000);

        $entry = $this->entry();

        self::assertSame('10000', (string) $entry->getTaxAmount());
        self::assertSame('50000', (string) $entry->getNetAmount());
    }

    /**
     * A bill whose tax was never recorded — the supplier charged none, or the
     * company is outside the scope of VAT and never asked. Nothing is invented.
     */
    public function testABillWithNoTaxRecordedDeductsNothing(): void
    {
        $this->payment($this->bill(120_000, null), 120_000);

        $entry = $this->entry();

        self::assertFalse($entry->hasTax());
        self::assertNull($entry->getTaxAmount());
    }

    /**
     * The two sides of a return are opposite signs, so the seal keeps them
     * apart. One figure standing for both would be their difference, which is
     * the thing the return exists to work out.
     */
    public function testClosingAPeriodFreezesTaxPaidApartFromTaxCollected(): void
    {
        $this->payment($this->bill(120_000, 20_000), 120_000);

        $period = $this->entry()->getPeriod();
        self::assertInstanceOf(AccountingPeriod::class, $period);

        self::getContainer()->get(AccountingPeriodManager::class)
            ->close($period, null, $period->getEndDate()->modify('+1 day'));

        $totals = $period->getTotals();

        self::assertSame('20000', $totals['tax_deductible'] ?? null);
        self::assertArrayNotHasKey('tax_collected', $totals, 'Nothing was sold, so nothing was collected.');
    }

    private function bill(int $total, ?int $tax): Bill
    {
        $bill = new Bill();
        $bill->setSupplier($this->entityManager->find(Client::class, $this->supplier->getId()));
        $bill->setTotalAmount(BigInteger::of($total));
        $bill->setCurrencyCode('EUR');
        $bill->setIssueDate(new DateTimeImmutable('2026-01-05'));
        $bill->setCompany($this->entityManager->find(Company::class, $this->company->getId()));

        if (null !== $tax) {
            $bill->setTaxAmount(BigInteger::of($tax));
        }

        $this->entityManager->persist($bill);
        $this->entityManager->flush();

        return $bill;
    }

    private function payment(Bill $bill, int $amount): void
    {
        $payment = new BillPayment();
        $payment->setBill($bill);
        $payment->setAmount(BigInteger::of($amount));
        $payment->setCurrencyCode('EUR');
        $payment->setPaidDate(new DateTimeImmutable('2026-01-15'));
        $payment->setMethod(BillPaymentMethod::BankTransfer);
        $payment->setCompany($this->entityManager->find(Company::class, $this->company->getId()));

        $this->entityManager->persist($payment);
        $this->entityManager->flush();
    }

    private function entry(): LedgerEntry
    {
        $this->entityManager->clear();

        $entries = self::getContainer()->get(LedgerEntryRepository::class)
            ->findBy(['book' => LedgerBook::Purchase]);

        self::assertCount(1, $entries);

        return $entries[0];
    }
}
