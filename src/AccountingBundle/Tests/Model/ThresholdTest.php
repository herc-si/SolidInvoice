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
use Augias\AccountingBundle\Model\Threshold;
use Brick\Math\BigInteger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Threshold::class)]
final class ThresholdTest extends TestCase
{
    private static function threshold(int $amount = 7_770_000): Threshold
    {
        return new Threshold(
            'micro_ceiling.services_bnc',
            'accounting.threshold.micro_ceiling',
            BigInteger::of($amount),
            'EUR',
            ActivityNature::ServicesBnc,
        );
    }

    public function testUsageRatio(): void
    {
        $threshold = self::threshold();

        self::assertSame('0.0000', (string) $threshold->usageRatio(BigInteger::zero())->toScale(4));
        self::assertSame('50.0000', (string) $threshold->usageRatio(BigInteger::of(3_885_000))->toScale(4));
        self::assertSame('100.0000', (string) $threshold->usageRatio(BigInteger::of(7_770_000))->toScale(4));
        self::assertSame('120.0000', (string) $threshold->usageRatio(BigInteger::of(9_324_000))->toScale(4));
    }

    /**
     * A limit of zero means "not configured", and dividing by it would either
     * blow up or report an infinite overrun. Reporting it as untouched is the
     * only reading that does not mislead.
     */
    public function testZeroThresholdReadsAsUntouched(): void
    {
        $threshold = self::threshold(0);

        self::assertTrue($threshold->usageRatio(BigInteger::of(1_000_000))->isZero());
        self::assertFalse($threshold->isExceededBy(BigInteger::of(1_000_000)));
    }

    public function testIsExceededByIsStrict(): void
    {
        $threshold = self::threshold();

        self::assertFalse($threshold->isExceededBy(BigInteger::of(7_769_999)));
        // Exactly at the limit is not over it.
        self::assertFalse($threshold->isExceededBy(BigInteger::of(7_770_000)));
        self::assertTrue($threshold->isExceededBy(BigInteger::of(7_770_001)));
    }

    public function testProratesToPartialYear(): void
    {
        // Started on 1 July: 184 of 365 days traded.
        $prorated = self::threshold()->proratedTo(184, 365);

        self::assertTrue($prorated->prorated);
        // 7_770_000 * 184 / 365 = 3_916_931.5..., rounded half-up.
        self::assertSame('3916932', (string) $prorated->amount);
        // Identity is preserved so alerts still key off the same threshold.
        self::assertSame('micro_ceiling.services_bnc', $prorated->key);
        self::assertSame(ActivityNature::ServicesBnc, $prorated->nature);
    }

    public function testAFullYearIsNotProrated(): void
    {
        $threshold = self::threshold();

        self::assertSame($threshold, $threshold->proratedTo(365, 365));
        self::assertSame($threshold, $threshold->proratedTo(400, 365));
        self::assertSame($threshold, $threshold->proratedTo(100, 0));
    }
}
