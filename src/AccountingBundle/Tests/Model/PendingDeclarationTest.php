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

namespace Augias\AccountingBundle\Tests\Model;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Enum\DeclarationAction;
use Augias\AccountingBundle\Enum\PeriodStatus;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Model\PendingDeclaration;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PendingDeclaration::class)]
final class PendingDeclarationTest extends TestCase
{
    /**
     * An open period has to be sealed before its figures mean anything, so that
     * is the step the user is on whatever else is true.
     */
    public function testAnOpenPeriodHasToBeClosedFirst(): void
    {
        $pending = new PendingDeclaration(self::period(PeriodStatus::Open));

        self::assertSame(DeclarationAction::Close, $pending->action);
    }

    public function testAClosedPeriodIsWaitingToBeFiled(): void
    {
        $pending = new PendingDeclaration(self::period(PeriodStatus::Closed));

        self::assertSame(DeclarationAction::File, $pending->action);
    }

    /**
     * A declaration row that does not exist is not an error: DeclarationBuilder
     * computes one the first time somebody opens the period's screen, so a
     * quarter nobody has looked at yet has none and is undeclared all the same.
     */
    public function testAMissingDeclarationIsNotAnError(): void
    {
        $pending = new PendingDeclaration(self::period(PeriodStatus::Closed));

        self::assertNull($pending->declaration);
        self::assertSame(DeclarationAction::File, $pending->action);
    }

    public function testCountsWholeDaysSinceThePeriodEnded(): void
    {
        $pending = new PendingDeclaration(self::period(PeriodStatus::Closed));

        self::assertSame(41, $pending->daysSincePeriodEnded(new DateTimeImmutable('2026-08-10')));
        self::assertSame(0, $pending->daysSincePeriodEnded(new DateTimeImmutable('2026-06-30')));
    }

    private static function period(PeriodStatus $status): AccountingPeriod
    {
        return new AccountingPeriod()
            ->setType(PeriodType::Quarter)
            ->setYear(2026)
            ->setOrdinal(2)
            ->setStartDate(new DateTimeImmutable('2026-04-01'))
            ->setEndDate(new DateTimeImmutable('2026-06-30'))
            ->setStatus($status);
    }
}
