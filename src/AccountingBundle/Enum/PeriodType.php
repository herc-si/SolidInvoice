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

namespace Augias\AccountingBundle\Enum;

use DateTimeImmutable;
use function floor;
use function sprintf;

/**
 * The granularity a company closes its books at. It follows the declaration
 * periodicity chosen in the settings — a micro-entrepreneur declares turnover
 * either monthly or quarterly, and closing on the same rhythm is what makes a
 * period's frozen totals line up with what was actually declared.
 *
 * {@see self::Year} exists for regimes that only ever report annually, and as
 * the unit the regime ceilings are measured against.
 */
enum PeriodType: string
{
    case Month = 'month';

    case Quarter = 'quarter';

    case Year = 'year';

    public function getLabel(): string
    {
        return match ($this) {
            self::Month => 'Monthly',
            self::Quarter => 'Quarterly',
            self::Year => 'Yearly',
        };
    }

    public function translationKey(): string
    {
        return 'accounting.period_type.' . $this->value;
    }

    /**
     * First day of the period the given date falls in, at midnight.
     */
    public function startOf(DateTimeImmutable $date, int $fiscalYearStartMonth = 1): DateTimeImmutable
    {
        $date = $date->setTime(0, 0);

        return match ($this) {
            // Months and quarters are calendar ones whatever the company's
            // financial year does. A VAT quarter is January to March for
            // everybody; only the year moves.
            self::Month => $date->modify('first day of this month'),
            self::Quarter => $date
                ->setDate((int) $date->format('Y'), (self::quarterOf($date) - 1) * 3 + 1, 1),
            self::Year => $date->setDate(self::yearOf($date, $fiscalYearStartMonth), $fiscalYearStartMonth, 1),
        };
    }

    /**
     * Last day of the period the given date falls in, at midnight. Inclusive —
     * ledger entries are dated, not timestamped, so a closed-interval
     * comparison is the honest one here.
     */
    public function endOf(DateTimeImmutable $date, int $fiscalYearStartMonth = 1): DateTimeImmutable
    {
        return match ($this) {
            self::Month => $this->startOf($date)->modify('last day of this month'),
            self::Quarter => $this->startOf($date)->modify('+2 months')->modify('last day of this month'),
            // Twelve months from the day it opened, minus a day. Derived rather
            // than written as "31 December", which is only right for a
            // financial year that happens to start in January.
            self::Year => $this->startOf($date, $fiscalYearStartMonth)
                ->modify('+1 year')
                ->modify('-1 day'),
        };
    }

    /**
     * The year a period is filed under.
     *
     * For a financial year that does not start in January, that is the year it
     * *opened* in: an exercice running April 2026 to March 2027 is 2026's, and
     * every date inside it has to agree on that or the same exercice would be
     * created twice.
     */
    public function yearOf(DateTimeImmutable $date, int $fiscalYearStartMonth = 1): int
    {
        $year = (int) $date->format('Y');

        if ($this !== self::Year || 1 === $fiscalYearStartMonth) {
            return $year;
        }

        return (int) $date->format('n') < $fiscalYearStartMonth ? $year - 1 : $year;
    }

    /**
     * The ordinal of the period within its year: 1-12 for months, 1-4 for
     * quarters, always 1 for a year. Stored on the period so it can be sorted
     * and addressed without re-deriving it from the dates.
     */
    public function ordinalOf(DateTimeImmutable $date): int
    {
        return match ($this) {
            self::Month => (int) $date->format('n'),
            self::Quarter => self::quarterOf($date),
            self::Year => 1,
        };
    }

    /**
     * A short, locale-independent label such as "2026-03", "2026-Q1" or "2026".
     * Used as the period's stable identifier in exports and declarations, where
     * a translated month name would be a liability.
     */
    public function formatLabel(int $year, int $ordinal, int $fiscalYearStartMonth = 1): string
    {
        return match ($this) {
            self::Month => sprintf('%d-%02d', $year, $ordinal),
            self::Quarter => sprintf('%d-Q%d', $year, $ordinal),
            // A financial year that straddles two calendar ones is named after
            // both, the way every accountant writes it. Calling it "2026" alone
            // would leave the reader to guess which twelve months are meant.
            self::Year => 1 === $fiscalYearStartMonth
                ? (string) $year
                : sprintf('%d-%d', $year, $year + 1),
        };
    }

    private static function quarterOf(DateTimeImmutable $date): int
    {
        return (int) floor(((int) $date->format('n') - 1) / 3) + 1;
    }
}
