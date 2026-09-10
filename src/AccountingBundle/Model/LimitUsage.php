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

namespace Augias\AccountingBundle\Model;

use Augias\AccountingBundle\Enum\LimitSeverity;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use function min;

/**
 * One limit and how much of it this year's turnover has used up.
 *
 * The percentage is a whole number because that is as fine as any of this
 * reads, and because a template that has to size a bar and compare against a
 * boundary should not be unwrapping a BigDecimal to do it.
 *
 * @see \Augias\AccountingBundle\Tests\Model\LimitUsageTest
 */
final readonly class LimitUsage
{
    public LimitSeverity $severity;

    public function __construct(
        public Threshold $threshold,
        public int $used,
    ) {
        $this->severity = LimitSeverity::forUsage($used);
    }

    public static function for(Threshold $threshold, TurnoverSummary $turnover): self
    {
        return new self($threshold, self::percentage($threshold, $turnover));
    }

    /**
     * How wide to draw the bar: the same figure, held at 100.
     *
     * The number beside the bar keeps saying 143% — the overshoot is the whole
     * point of showing it — but a bar cannot be wider than its track.
     */
    public function barWidth(): int
    {
        return min($this->used, 100);
    }

    /**
     * Whether this limit has anything to measure at all.
     *
     * A services business does not need a row telling it that it has used 0% of
     * the ceiling for selling goods, so a limit on an activity with no turnover
     * behind it is left out rather than drawn empty.
     */
    public static function measurable(Threshold $threshold, TurnoverSummary $turnover): bool
    {
        return ! self::amountFor($threshold, $turnover)->isZero();
    }

    private static function percentage(Threshold $threshold, TurnoverSummary $turnover): int
    {
        return $threshold->usageRatio(self::amountFor($threshold, $turnover))
            ->toScale(0, RoundingMode::HalfUp)
            ->toInt();
    }

    /**
     * A limit tied to an activity is measured against that activity's own
     * turnover; one that spans every activity against the lot.
     */
    private static function amountFor(Threshold $threshold, TurnoverSummary $turnover): BigInteger
    {
        return null === $threshold->nature
            ? $turnover->total()
            : $turnover->forNature($threshold->nature);
    }
}
