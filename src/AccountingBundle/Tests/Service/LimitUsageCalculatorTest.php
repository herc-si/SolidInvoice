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

use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Model\LimitUsage;
use Augias\AccountingBundle\Model\Threshold;
use Augias\AccountingBundle\Model\ThresholdSet;
use Augias\AccountingBundle\Model\TurnoverSummary;
use Augias\AccountingBundle\Regime\RegimeInterface;
use Augias\AccountingBundle\Service\LimitUsageCalculator;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LimitUsageCalculator::class)]
final class LimitUsageCalculatorTest extends TestCase
{
    private LimitUsageCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new LimitUsageCalculator();
    }

    /**
     * Limits on an activity the company has taken no money in are dropped, not
     * drawn at zero.
     */
    public function testKeepsOnlyTheLimitsThatMeasureSomething(): void
    {
        $limits = $this->calculator->forTurnover(
            $this->regime(new ThresholdSet([
                self::threshold('services', ActivityNature::ServicesBnc),
                self::threshold('goods', ActivityNature::SaleOfGoods),
            ])),
            self::profile(),
            self::turnover([ActivityNature::ServicesBnc->value => BigInteger::of(3_885_000)]),
            new DateTimeImmutable('2026-09-10'),
        );

        self::assertCount(1, $limits);
        self::assertSame('services', $limits[0]->threshold->key);
        self::assertSame(50, $limits[0]->used);
    }

    public function testARegimeWithNoLimitsMeasuresNothing(): void
    {
        $limits = $this->calculator->forTurnover(
            $this->regime(new ThresholdSet()),
            self::profile(),
            self::turnover([ActivityNature::ServicesBnc->value => BigInteger::of(3_885_000)]),
            new DateTimeImmutable('2026-09-10'),
        );

        self::assertSame([], $limits);
    }

    /**
     * A card that leads with a limit at 3% while one sits at 94% further down
     * has buried the only line that mattered.
     */
    public function testMostPressingKeepsTheWorstOnesFirst(): void
    {
        $limits = [
            new LimitUsage(self::threshold('quiet', null), 3),
            new LimitUsage(self::threshold('loud', null), 94),
            new LimitUsage(self::threshold('middling', null), 40),
        ];

        $kept = $this->calculator->mostPressing($limits, 2);

        self::assertSame(['loud', 'middling'], array_map(
            static fn (LimitUsage $usage): string => $usage->threshold->key,
            $kept,
        ));
    }

    public function testMostPressingAsksForMoreThanThereAre(): void
    {
        $limits = [new LimitUsage(self::threshold('only', null), 12)];

        self::assertCount(1, $this->calculator->mostPressing($limits, 5));
    }

    private function regime(ThresholdSet $thresholds): RegimeInterface
    {
        $regime = $this->createStub(RegimeInterface::class);
        $regime->method('thresholds')
            ->willReturn($thresholds);

        return $regime;
    }

    private static function threshold(string $key, ?ActivityNature $nature): Threshold
    {
        return new Threshold(
            $key,
            'accounting.threshold.micro_ceiling',
            BigInteger::of(7_770_000),
            'EUR',
            $nature,
        );
    }

    private static function profile(): AccountingProfile
    {
        return new AccountingProfile(
            regimeCode: 'fr_micro',
            vatExempt: true,
            vatExemptMention: '',
            activityStartDate: null,
            primaryActivity: ActivityNature::ServicesBnc,
            declarationPeriodicity: PeriodType::Quarter,
            vatPeriodicity: null,
            fiscalYearStartMonth: 1,
            currencyCode: 'EUR',
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
