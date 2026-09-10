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

use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\LimitSeverity;
use Augias\AccountingBundle\Model\LimitUsage;
use Augias\AccountingBundle\Model\Threshold;
use Augias\AccountingBundle\Model\TurnoverSummary;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LimitUsage::class)]
final class LimitUsageTest extends TestCase
{
    public function testMeasuresAnActivityLimitAgainstThatActivityAlone(): void
    {
        $usage = LimitUsage::for(self::threshold(ActivityNature::ServicesBnc), self::turnover([
            ActivityNature::ServicesBnc->value => BigInteger::of(3_885_000),
            ActivityNature::SaleOfGoods->value => BigInteger::of(9_000_000),
        ]));

        // Half of the services ceiling. The goods turnover beside it is measured
        // by the goods ceiling and must not leak into this one.
        self::assertSame(50, $usage->used);
        self::assertSame(LimitSeverity::Ok, $usage->severity);
    }

    public function testMeasuresAGlobalLimitAgainstEverything(): void
    {
        $usage = LimitUsage::for(self::threshold(null), self::turnover([
            ActivityNature::ServicesBnc->value => BigInteger::of(3_885_000),
            ActivityNature::SaleOfGoods->value => BigInteger::of(3_885_000),
        ]));

        self::assertSame(100, $usage->used);
        self::assertSame(LimitSeverity::Exceeded, $usage->severity);
    }

    /**
     * The number keeps saying 143% — the overshoot is the whole point of showing
     * it — but a bar cannot be wider than its track.
     */
    public function testBarStopsAtFullWhileTheFigureDoesNot(): void
    {
        $usage = LimitUsage::for(self::threshold(null), self::turnover([
            ActivityNature::ServicesBnc->value => BigInteger::of(11_110_000),
        ]));

        self::assertSame(143, $usage->used);
        self::assertSame(100, $usage->barWidth());
    }

    /**
     * A services business does not need a row telling it that it has used 0% of
     * the ceiling for selling goods.
     */
    public function testALimitWithNoTurnoverBehindItIsNotMeasurable(): void
    {
        $turnover = self::turnover([ActivityNature::ServicesBnc->value => BigInteger::of(1_000_000)]);

        self::assertTrue(LimitUsage::measurable(self::threshold(ActivityNature::ServicesBnc), $turnover));
        self::assertFalse(LimitUsage::measurable(self::threshold(ActivityNature::SaleOfGoods), $turnover));
    }

    private static function threshold(?ActivityNature $nature): Threshold
    {
        return new Threshold(
            'micro_ceiling.test',
            'accounting.threshold.micro_ceiling',
            BigInteger::of(7_770_000),
            'EUR',
            $nature,
        );
    }

    /**
     * @param array<string, BigInteger> $byNature
     */
    private static function turnover(array $byNature): TurnoverSummary
    {
        return new TurnoverSummary(
            from: new DateTimeImmutable('2026-01-01'),
            to: new DateTimeImmutable('2026-09-10'),
            currencyCode: 'EUR',
            byNature: $byNature,
        );
    }
}
