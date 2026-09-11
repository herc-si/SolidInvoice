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

namespace Augias\AccountingBundle\Tests\Service;

use Augias\AccountingBundle\AccountingSettings;
use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Enum\PeriodStatus;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Service\LedgerLockDate;
use Augias\CoreBundle\Entity\Company;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\SettingsBundle\SystemConfig;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The date the books are shut up to, and what it refuses.
 */
#[CoversClass(LedgerLockDate::class)]
final class LedgerLockDateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private EntityManagerInterface $entityManager;

    private SystemConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $config = self::getContainer()->get(SystemConfig::class);
        self::assertInstanceOf(SystemConfig::class, $config);
        $this->config = $config;

        $this->config->set(SystemConfig::CURRENCY_CONFIG_PATH, 'EUR');
        $this->config->set(AccountingSettings::DECLARATION_PERIODICITY, PeriodType::Quarter->value);
    }

    public function testNothingIsShutOnABookThatHasNeitherSettingNorSealedPeriod(): void
    {
        self::assertNull($this->lockDate()->forCompany($this->companyReference()));
    }

    public function testTheDateTheUserSetIsWhatShutsTheBooks(): void
    {
        $this->config->set(AccountingSettings::LOCK_DATE, '2026-06-30');

        self::assertSame('2026-06-30', $this->lockDate()->forCompany($this->companyReference())?->format('Y-m-d'));
    }

    /**
     * Nothing has to be written when a period is sealed: the seal is read as a
     * floor, so the two can never disagree.
     */
    public function testSealingAPeriodShutsTheBooksWithNoSettingAtAll(): void
    {
        $this->period(2026, 1, '2026-01-01', '2026-03-31', PeriodStatus::Closed);

        self::assertSame('2026-03-31', $this->lockDate()->forCompany($this->companyReference())?->format('Y-m-d'));
    }

    public function testTheLaterOfTheTwoWins(): void
    {
        $this->period(2026, 1, '2026-01-01', '2026-03-31', PeriodStatus::Closed);
        $this->config->set(AccountingSettings::LOCK_DATE, '2026-06-30');

        self::assertSame('2026-06-30', $this->lockDate()->forCompany($this->companyReference())?->format('Y-m-d'));
    }

    /**
     * The user can move the date back, but not behind what is already sealed —
     * a seal is final, and a setting that claims otherwise is ignored rather
     * than obeyed.
     */
    public function testASettingBeforeWhatIsSealedHasNoEffect(): void
    {
        $this->period(2026, 2, '2026-04-01', '2026-06-30', PeriodStatus::Closed);
        $this->config->set(AccountingSettings::LOCK_DATE, '2026-01-31');

        self::assertSame('2026-06-30', $this->lockDate()->forCompany($this->companyReference())?->format('Y-m-d'));
    }

    /**
     * A hand-edited value is not a reason to unlock the books; what is sealed
     * still holds.
     */
    public function testAValueThatCannotBeReadFallsBackOnTheSeal(): void
    {
        $this->period(2026, 1, '2026-01-01', '2026-03-31', PeriodStatus::Closed);
        $this->config->set(AccountingSettings::LOCK_DATE, 'not a date at all');

        self::assertSame('2026-03-31', $this->lockDate()->forCompany($this->companyReference())?->format('Y-m-d'));
    }

    public function testAnEntryInAPeriodThatEndedBeforeTheLockDateIsShut(): void
    {
        $this->config->set(AccountingSettings::LOCK_DATE, '2026-06-30');
        $period = $this->period(2026, 2, '2026-04-01', '2026-06-30', PeriodStatus::Open);

        self::assertTrue($this->lockDate()->shuts($this->entry($period, new DateTimeImmutable('2026-05-12'))));
    }

    public function testAnEntryInTheRunningPeriodIsNotShut(): void
    {
        $this->config->set(AccountingSettings::LOCK_DATE, '2026-06-30');
        $period = $this->period(2026, 3, '2026-07-01', '2026-09-30', PeriodStatus::Open);

        self::assertFalse($this->lockDate()->shuts($this->entry($period, new DateTimeImmutable('2026-07-04'))));
    }

    /**
     * Money that turns up late keeps the date it moved on but is filed into the
     * period still open. Reading the entry's own date would refuse a correction
     * to it; the period it sits in is what counts.
     */
    public function testALateEntryDatedBeforeTheLockStaysCorrectable(): void
    {
        $this->config->set(AccountingSettings::LOCK_DATE, '2026-06-30');
        $open = $this->period(2026, 3, '2026-07-01', '2026-09-30', PeriodStatus::Open);

        $entry = $this->entry($open, new DateTimeImmutable('2026-05-12'))
            ->setLateEntry(true);

        self::assertFalse($this->lockDate()->shuts($entry));
    }

    private function lockDate(): LedgerLockDate
    {
        $lockDate = self::getContainer()->get(LedgerLockDate::class);
        self::assertInstanceOf(LedgerLockDate::class, $lockDate);
        $lockDate->reset();

        return $lockDate;
    }

    private function period(int $year, int $ordinal, string $start, string $end, PeriodStatus $status): AccountingPeriod
    {
        $period = new AccountingPeriod()
            ->setType(PeriodType::Quarter)
            ->setYear($year)
            ->setOrdinal($ordinal)
            ->setStartDate(new DateTimeImmutable($start))
            ->setEndDate(new DateTimeImmutable($end))
            ->setStatus($status);

        $period->setCompany($this->companyReference());

        $this->entityManager->persist($period);
        $this->entityManager->flush();

        return $period;
    }

    private function entry(AccountingPeriod $period, DateTimeImmutable $on): LedgerEntry
    {
        $entry = new LedgerEntry()
            ->setBook(LedgerBook::Revenue)
            ->setEntryDate($on)
            ->setLabel('Invoice payment')
            ->setCounterpartyName('Johnston PLC')
            ->setAmount(BigInteger::of(100_000))
            ->setCurrencyCode('EUR')
            ->setActivityNature(ActivityNature::ServicesBnc)
            ->setPeriod($period);

        $entry->setCompany($this->companyReference());

        return $entry;
    }

    private function companyReference(): Company
    {
        $company = $this->entityManager->find(Company::class, $this->company->getId());
        self::assertInstanceOf(Company::class, $company);

        return $company;
    }
}
