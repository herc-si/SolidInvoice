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

namespace Augias\AccountingBundle\Tests\Enum;

use Augias\AccountingBundle\Enum\PeriodType;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * A financial year that does not open in January.
 */
#[CoversClass(PeriodType::class)]
final class PeriodTypeFiscalYearTest extends TestCase
{
    public function testAYearOpeningInJanuaryIsTheCalendarYear(): void
    {
        $date = new DateTimeImmutable('2026-08-10');

        self::assertSame('2026-01-01', PeriodType::Year->startOf($date)->format('Y-m-d'));
        self::assertSame('2026-12-31', PeriodType::Year->endOf($date)->format('Y-m-d'));
        self::assertSame(2026, PeriodType::Year->yearOf($date));
        self::assertSame('2026', PeriodType::Year->formatLabel(2026, 1));
    }

    public function testAnAprilYearRunsToTheFollowingMarch(): void
    {
        $date = new DateTimeImmutable('2026-08-10');

        self::assertSame('2026-04-01', PeriodType::Year->startOf($date, 4)->format('Y-m-d'));
        self::assertSame('2027-03-31', PeriodType::Year->endOf($date, 4)->format('Y-m-d'));
    }

    /**
     * The months before the year opens belong to the one that opened the
     * previous calendar year. Getting this wrong would file the same exercice
     * under two different years and create it twice.
     */
    public function testADateBeforeTheOpeningMonthBelongsToTheYearBefore(): void
    {
        $march = new DateTimeImmutable('2027-03-15');

        self::assertSame(2026, PeriodType::Year->yearOf($march, 4));
        self::assertSame('2026-04-01', PeriodType::Year->startOf($march, 4)->format('Y-m-d'));
        self::assertSame('2027-03-31', PeriodType::Year->endOf($march, 4)->format('Y-m-d'));
    }

    public function testTheDayItOpensAndTheDayItClosesBothFallInside(): void
    {
        foreach (['2026-04-01', '2027-03-31'] as $day) {
            $date = new DateTimeImmutable($day);

            self::assertSame('2026-04-01', PeriodType::Year->startOf($date, 4)->format('Y-m-d'), $day);
            self::assertSame('2027-03-31', PeriodType::Year->endOf($date, 4)->format('Y-m-d'), $day);
        }
    }

    /**
     * A leap year does not shorten it: twelve months from the day it opened,
     * minus a day, however many days that is.
     */
    public function testALeapYearIsStillTwelveMonths(): void
    {
        $date = new DateTimeImmutable('2027-06-15');

        self::assertSame('2027-03-01', PeriodType::Year->startOf($date, 3)->format('Y-m-d'));
        self::assertSame('2028-02-29', PeriodType::Year->endOf($date, 3)->format('Y-m-d'));
    }

    public function testAStraddlingYearIsNamedAfterBoth(): void
    {
        self::assertSame('2026-2027', PeriodType::Year->formatLabel(2026, 1, 4));
    }

    /**
     * Months and quarters are calendar ones whatever the exercice does: a VAT
     * quarter is January to March for everybody.
     */
    public function testMonthsAndQuartersIgnoreTheOpeningMonth(): void
    {
        $date = new DateTimeImmutable('2026-08-10');

        self::assertSame('2026-08-01', PeriodType::Month->startOf($date, 4)->format('Y-m-d'));
        self::assertSame('2026-07-01', PeriodType::Quarter->startOf($date, 4)->format('Y-m-d'));
        self::assertSame(2026, PeriodType::Quarter->yearOf($date, 4));
    }
}
