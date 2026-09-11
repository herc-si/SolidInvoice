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
use Augias\AccountingBundle\Enum\DeclarationKind;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Model\DeclarationLine;
use Augias\AccountingBundle\Regime\Fr\MicroEntrepriseRegime;
use Augias\AccountingBundle\Service\AccountingPeriodManager;
use Augias\AccountingBundle\Service\DeclarationBuilder;
use Augias\AccountingBundle\Service\VatReturnCalculator;
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
 * The VAT return, gathered from the books rather than computed from a rate.
 */
#[CoversClass(VatReturnCalculator::class)]
#[CoversClass(DeclarationBuilder::class)]
#[Group('functional')]
final class VatReturnTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private EntityManagerInterface $entityManager;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $config = self::getContainer()->get(SystemConfig::class);
        $config->set(SystemConfig::CURRENCY_CONFIG_PATH, 'EUR');
        $config->set(AccountingSettings::REGIME, MicroEntrepriseRegime::CODE);
        $config->set(AccountingSettings::PRIMARY_ACTIVITY, ActivityNature::SaleOfGoods->value);
        $config->set(AccountingSettings::DECLARATION_PERIODICITY, PeriodType::Quarter->value);
        // Past the franchise threshold: in the scope of VAT, and still a
        // micro-entreprise declaring turnover to URSSAF.
        $config->set(AccountingSettings::VAT_EXEMPT, '0');

        $this->period = self::getContainer()->get(AccountingPeriodManager::class)
            ->periodFor($this->companyReference(), PeriodType::Quarter, new DateTimeImmutable('2026-02-10'));

        $this->entityManager->flush();
    }

    /**
     * The two returns are not rivals: a micro-entrepreneur past the threshold
     * owes both for the same quarter, which is the whole reason a declaration
     * is no longer unique per period.
     */
    public function testAPeriodInTheScopeOfVatOwesTwoReturns(): void
    {
        $kinds = self::getContainer()->get(DeclarationBuilder::class)
            ->kindsOwed(self::getContainer()->get('Augias\AccountingBundle\Service\AccountingProfileProvider')->forCompany($this->companyReference()));

        self::assertSame([DeclarationKind::SocialContributions, DeclarationKind::Vat], $kinds);
    }

    public function testCollectedVatIsReportedPerRateAndDeductedAsOneFigure(): void
    {
        $this->sale(120_000, [['rate' => '20.0000', 'category' => 'Standard', 'base' => '100000', 'tax' => '20000']]);
        $this->sale(105_500, [['rate' => '5.5000', 'category' => 'Standard', 'base' => '100000', 'tax' => '5500']]);
        $this->purchase(60_000, 10_000);

        $result = self::getContainer()->get(VatReturnCalculator::class)->calculate($this->period, 'EUR');

        self::assertCount(3, $result->lines);

        // Sorted by rate, so the return reads in the order its boxes do.
        self::assertSame('5.5000', (string) $result->lines[0]->rate);
        self::assertSame('5500', (string) $result->lines[0]->amount);
        self::assertSame('20.0000', (string) $result->lines[1]->rate);
        self::assertSame('20000', (string) $result->lines[1]->amount);

        $deducted = $result->lines[2];

        self::assertSame(DeclarationLine::KIND_VAT_DEDUCTIBLE, $deducted->kind);
        self::assertSame('-10000', (string) $deducted->amount, 'Deducted VAT is negative, so the lines add up to the balance.');

        // 25 500 collected less 10 000 deducted.
        self::assertSame('15500', (string) $result->totalDue());
        self::assertSame('200000', (string) $result->turnover, 'The taxable base, not the money received.');
    }

    /**
     * More deducted than collected is not a debt owed backwards — it is a
     * credit, and the figure has to come out negative for the total to say so.
     */
    public function testDeductingMoreThanWasCollectedProducesACredit(): void
    {
        $this->sale(12_000, [['rate' => '20.0000', 'category' => 'Standard', 'base' => '10000', 'tax' => '2000']]);
        $this->purchase(60_000, 10_000);

        $result = self::getContainer()->get(VatReturnCalculator::class)->calculate($this->period, 'EUR');

        self::assertSame('-8000', (string) $result->totalDue());
    }

    public function testNothingTaxableInThePeriodProducesNoLines(): void
    {
        $result = self::getContainer()->get(VatReturnCalculator::class)->calculate($this->period, 'EUR');

        self::assertSame([], $result->lines);
        self::assertSame('0', (string) $result->totalDue());
    }

    /**
     * Stored alongside the turnover declaration rather than instead of it, each
     * with its own status and its own reference to record.
     */
    public function testBothReturnsAreStoredForTheSamePeriod(): void
    {
        $this->sale(120_000, [['rate' => '20.0000', 'category' => 'Standard', 'base' => '100000', 'tax' => '20000']]);

        $builder = self::getContainer()->get(DeclarationBuilder::class);

        $social = $builder->forPeriod($this->period, DeclarationKind::SocialContributions);
        $vat = $builder->forPeriod($this->period, DeclarationKind::Vat);

        self::assertNotSame($social->getId(), $vat->getId());
        self::assertSame(DeclarationKind::Vat, $vat->getKind());
        self::assertSame('20000', (string) $vat->getTotalDue());
        self::assertNull($vat->getRateVersion(), 'VAT comes from the documents, not from a rate table that can go stale.');
    }

    /**
     * @param list<array{rate: string, category: string, base: string, tax: string}> $breakdown
     */
    private function sale(int $amount, array $breakdown): void
    {
        $tax = BigInteger::zero();

        foreach ($breakdown as $share) {
            $tax = $tax->plus($share['tax']);
        }

        $entry = $this->entry(LedgerBook::Revenue, $amount);
        $entry->setActivityNature(ActivityNature::SaleOfGoods)
            ->setTax(BigInteger::of($amount)->minus($tax), $tax, $breakdown);

        $this->entityManager->flush();
    }

    private function purchase(int $amount, int $tax): void
    {
        $this->entry(LedgerBook::Purchase, $amount)
            ->setTax(BigInteger::of($amount - $tax), BigInteger::of($tax), []);

        $this->entityManager->flush();
    }

    private function entry(LedgerBook $book, int $amount): LedgerEntry
    {
        $entry = new LedgerEntry()
            ->setBook($book)
            ->setEntryDate(new DateTimeImmutable('2026-02-10'))
            ->setLabel('Operation')
            ->setCounterpartyName('Someone')
            ->setAmount(BigInteger::of($amount))
            ->setCurrencyCode('EUR')
            ->setPeriod($this->period);

        $entry->setCompany($this->companyReference());

        $this->entityManager->persist($entry);

        return $entry;
    }

    private function companyReference(): Company
    {
        $company = $this->entityManager->find(Company::class, $this->company->getId());
        self::assertInstanceOf(Company::class, $company);

        return $company;
    }
}
