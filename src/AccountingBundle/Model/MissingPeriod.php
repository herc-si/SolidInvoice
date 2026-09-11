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

use Augias\AccountingBundle\Enum\PeriodType;
use DateTimeImmutable;

/**
 * A period the calendar says a company should have, and has no row for.
 *
 * Periods are created when an entry is filed into one, so a quarter in which
 * nothing was received never comes into being — and a regime that still wants a
 * nil return for it has nothing to attach one to. This is what such a gap looks
 * like before anybody decides to do something about it.
 *
 * It has no id on purpose: nothing has been created. It is enough to name the
 * gap on screen, and the moment the user acts on it
 * {@see \Augias\AccountingBundle\Action\CreatePeriod} turns it into a real
 * period through the same factory the lazy path uses.
 *
 * @see \Augias\AccountingBundle\Tests\Model\MissingPeriodTest
 */
final readonly class MissingPeriod
{
    public function __construct(
        public PeriodType $type,
        public int $year,
        public int $ordinal,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
    ) {
    }

    /**
     * Built from any date inside the period, so callers never have to work out
     * an ordinal or a month boundary themselves.
     */
    public static function covering(PeriodType $type, DateTimeImmutable $date, int $fiscalYearStartMonth = 1): self
    {
        return new self(
            $type,
            $type->yearOf($date, $fiscalYearStartMonth),
            $type->ordinalOf($date),
            $type->startOf($date, $fiscalYearStartMonth),
            $type->endOf($date, $fiscalYearStartMonth),
        );
    }

    /**
     * "2026-Q1" and friends — the same label a real period carries, so a gap and
     * a row read identically in a list.
     */
    public function getLabel(): string
    {
        return $this->type->formatLabel($this->year, $this->ordinal, $this->fiscalYearStartMonth());
    }

    public function hasEnded(?DateTimeImmutable $on = null): bool
    {
        return $this->endDate < ($on ?? new DateTimeImmutable('today'))->setTime(0, 0);
    }

    /**
     * Read back off the dates rather than carried around: a period that opens
     * in April is an April-to-March financial year and says so in its label,
     * and the two can never disagree if only one of them is stored.
     */
    private function fiscalYearStartMonth(): int
    {
        return (int) $this->startDate->format('n');
    }
}
