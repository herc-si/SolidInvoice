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

namespace Augias\AccountingBundle\Service;

use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Model\LimitUsage;
use Augias\AccountingBundle\Model\TurnoverSummary;
use Augias\AccountingBundle\Regime\RegimeInterface;
use DateTimeImmutable;

/**
 * Every limit that has something to measure, with how much of it is used.
 *
 * This was private to the accounting home page until the dashboard needed the
 * same answer. Two callers computing "how close am I to the ceiling" from the
 * same inputs is exactly the kind of duplication that ends with a widget and a
 * page disagreeing about a number the user is making decisions on.
 *
 * @see \Augias\AccountingBundle\Tests\Service\LimitUsageCalculatorTest
 */
final readonly class LimitUsageCalculator
{
    /**
     * @return list<LimitUsage>
     */
    public function forTurnover(
        RegimeInterface $regime,
        AccountingProfile $profile,
        TurnoverSummary $turnover,
        DateTimeImmutable $on,
    ): array {
        $limits = [];

        // Resolved for the date being examined rather than for "now": limits
        // move with finance acts, and a figure on screen has to be the one that
        // actually applied.
        foreach ($regime->thresholds($profile, $on) as $threshold) {
            if (LimitUsage::measurable($threshold, $turnover)) {
                $limits[] = LimitUsage::for($threshold, $turnover);
            }
        }

        return $limits;
    }

    /**
     * The limits worth interrupting someone about, worst first.
     *
     * The dashboard has room for a couple of bars, not for every ceiling a
     * regime defines, and a card that leads with a limit at 3% while one sits
     * at 94% further down has buried the only line that mattered.
     *
     * @param list<LimitUsage> $limits
     * @return list<LimitUsage>
     */
    public function mostPressing(array $limits, int $keep): array
    {
        usort($limits, static fn (LimitUsage $a, LimitUsage $b): int => $b->used <=> $a->used);

        return array_slice($limits, 0, $keep);
    }
}
