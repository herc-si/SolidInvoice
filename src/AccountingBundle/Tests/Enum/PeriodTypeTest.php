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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PeriodType::class)]
final class PeriodTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{PeriodType, string, string, string, int}>
     */
    public static function boundaries(): iterable
    {
        yield 'mid-month' => [PeriodType::Month, '2026-03-17', '2026-03-01', '2026-03-31', 3];
        yield 'february in a leap year' => [PeriodType::Month, '2024-02-10', '2024-02-01', '2024-02-29', 2];
        yield 'first day of a month' => [PeriodType::Month, '2026-01-01', '2026-01-01', '2026-01-31', 1];
        yield 'last day of a month' => [PeriodType::Month, '2026-12-31', '2026-12-01', '2026-12-31', 12];

        yield 'start of Q1' => [PeriodType::Quarter, '2026-01-01', '2026-01-01', '2026-03-31', 1];
        yield 'end of Q1' => [PeriodType::Quarter, '2026-03-31', '2026-01-01', '2026-03-31', 1];
        yield 'mid Q2' => [PeriodType::Quarter, '2026-05-15', '2026-04-01', '2026-06-30', 2];
        yield 'mid Q3' => [PeriodType::Quarter, '2026-08-01', '2026-07-01', '2026-09-30', 3];
        yield 'end of Q4' => [PeriodType::Quarter, '2026-12-31', '2026-10-01', '2026-12-31', 4];
        // Q1 of a leap year still ends on 31 March, but the month inside it moves.
        yield 'Q1 of a leap year' => [PeriodType::Quarter, '2024-02-29', '2024-01-01', '2024-03-31', 1];

        yield 'year' => [PeriodType::Year, '2026-07-04', '2026-01-01', '2026-12-31', 1];
        yield 'leap year' => [PeriodType::Year, '2024-02-29', '2024-01-01', '2024-12-31', 1];
    }

    #[DataProvider('boundaries')]
    public function testResolvesPeriodBoundaries(
        PeriodType $type,
        string $date,
        string $expectedStart,
        string $expectedEnd,
        int $expectedOrdinal,
    ): void {
        $date = new DateTimeImmutable($date);

        self::assertSame($expectedStart, $type->startOf($date)->format('Y-m-d'));
        self::assertSame($expectedEnd, $type->endOf($date)->format('Y-m-d'));
        self::assertSame($expectedOrdinal, $type->ordinalOf($date));
    }

    /**
     * Boundaries have to ignore the time of day, otherwise an entry created in
     * the afternoon would fall outside the period it belongs to.
     */
    public function testBoundariesAreMidnight(): void
    {
        $afternoon = new DateTimeImmutable('2026-03-17 16:45:12');

        self::assertSame('2026-03-01 00:00:00', PeriodType::Month->startOf($afternoon)->format('Y-m-d H:i:s'));
        self::assertSame('2026-03-31 00:00:00', PeriodType::Month->endOf($afternoon)->format('Y-m-d H:i:s'));
    }

    public function testFormatsStableLabels(): void
    {
        self::assertSame('2026-03', PeriodType::Month->formatLabel(2026, 3));
        self::assertSame('2026-11', PeriodType::Month->formatLabel(2026, 11));
        self::assertSame('2026-Q2', PeriodType::Quarter->formatLabel(2026, 2));
        self::assertSame('2026', PeriodType::Year->formatLabel(2026, 1));
    }
}
