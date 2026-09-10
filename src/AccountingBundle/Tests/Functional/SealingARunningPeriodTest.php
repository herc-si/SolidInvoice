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

use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Exception\LedgerLockedException;
use Augias\AccountingBundle\Service\AccountingPeriodManager;
use Augias\CoreBundle\Entity\Company;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A period still running cannot be sealed.
 *
 * Sealing one used to be allowed, and the damage showed up one step later:
 * assignPeriod() files an entry whose own period is closed into the earliest
 * open one, falling back to the period today falls in — which was the closed
 * one. The entry landed inside a sealed period without a sequence number and
 * outside the hash chain the seal had just computed.
 *
 * That fallback's comment already assumed this could not happen — "it is open
 * by construction, since closing runs in date order". These tests are what make
 * the assumption true.
 */
#[CoversClass(AccountingPeriodManager::class)]
final class SealingARunningPeriodTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private EntityManagerInterface $entityManager;

    private AccountingPeriodManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $manager = self::getContainer()->get(AccountingPeriodManager::class);
        self::assertInstanceOf(AccountingPeriodManager::class, $manager);
        $this->manager = $manager;
    }

    public function testThePeriodTodayFallsInCannotBeSealed(): void
    {
        $period = $this->manager->periodFor($this->companyReference(), PeriodType::Quarter, new DateTimeImmutable('today'));
        $this->entityManager->flush();

        $this->expectException(LedgerLockedException::class);
        $this->expectExceptionMessage('cannot be closed before it has ended');

        $this->manager->close($period);
    }

    /**
     * Not even on its last day: an entry dated that day is still to come.
     */
    public function testNotEvenOnItsFinalDay(): void
    {
        $period = $this->manager->periodFor($this->companyReference(), PeriodType::Quarter, new DateTimeImmutable('today'));
        $this->entityManager->flush();

        $this->expectException(LedgerLockedException::class);

        $this->manager->close($period, null, $period->getEndDate());
    }

    public function testItCanBeSealedTheDayAfterItEnds(): void
    {
        $period = $this->manager->periodFor($this->companyReference(), PeriodType::Quarter, new DateTimeImmutable('today'));
        $this->entityManager->flush();

        $this->manager->close($period, null, $period->getEndDate()->modify('+1 day'));
        $this->entityManager->flush();

        self::assertTrue($period->isClosed());
    }

    /**
     * The defect this rule exists to prevent, from the other side.
     *
     * A payment recorded late and dated inside a quarter that has since been
     * sealed has to go somewhere open. With the rule in place the period today
     * falls in is always open — it cannot have been sealed — so that is where
     * it lands, flagged late. Without it, the fallback returned the sealed
     * period itself.
     */
    public function testALateEntryLandsInAnOpenPeriod(): void
    {
        $company = $this->companyReference();

        // A quarter well in the past, sealed the way production seals one.
        $past = $this->manager->periodFor($company, PeriodType::Quarter, new DateTimeImmutable('today')->modify('-6 months'));
        $this->entityManager->flush();
        $this->manager->close($past);
        $this->entityManager->flush();

        self::assertTrue($past->isClosed());

        $entry = new LedgerEntry()
            ->setBook(LedgerBook::Revenue)
            ->setEntryDate($past->getStartDate()->modify('+10 days'))
            ->setLabel('Payment recorded late')
            ->setCounterpartyName('Johnston PLC')
            ->setAmount(BigInteger::of(50_000))
            ->setCurrencyCode('EUR')
            ->setActivityNature(ActivityNature::ServicesBnc);

        $entry->setCompany($company);

        $this->manager->assignPeriod($entry, PeriodType::Quarter);

        $landed = $entry->getPeriod();

        self::assertNotNull($landed);
        self::assertTrue($landed->isOpen(), 'a late entry must never be filed into a sealed period');
        self::assertTrue($entry->isLateEntry());
        self::assertNotSame($past->getLabel(), $landed->getLabel());
    }

    private function companyReference(): Company
    {
        $company = $this->entityManager->find(Company::class, $this->company->getId());
        self::assertInstanceOf(Company::class, $company);

        return $company;
    }
}
