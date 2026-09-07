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

use Augias\AccountingBundle\Enum\ActivityNature;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Money\Currency;
use Money\Money;

/**
 * Cash-basis turnover over a date range, split by activity nature — the input
 * every ceiling check and every contribution calculation works from.
 *
 * One currency only. Ledger entries booked in any other currency are counted in
 * {@see $foreignCurrencies} and left out of the totals: silently converting
 * them would invent an exchange rate the books never recorded, and a mixed
 * ledger is a bookkeeping problem to surface, not to paper over.
 */
final readonly class TurnoverSummary
{
    /**
     * @param array<string, BigInteger> $byNature minor units, keyed by {@see ActivityNature::$value}
     * @param list<string>              $foreignCurrencies currency codes found but not included
     */
    public function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
        public string $currencyCode,
        public array $byNature = [],
        public array $foreignCurrencies = [],
    ) {
    }

    public function forNature(ActivityNature $nature): BigInteger
    {
        return $this->byNature[$nature->value] ?? BigInteger::zero();
    }

    public function total(): BigInteger
    {
        $total = BigInteger::zero();

        foreach ($this->byNature as $amount) {
            $total = $total->plus($amount);
        }

        return $total;
    }

    public function totalMoney(): Money
    {
        return new Money((string) $this->total(), new Currency($this->currencyCode));
    }

    public function moneyForNature(ActivityNature $nature): Money
    {
        return new Money((string) $this->forNature($nature), new Currency($this->currencyCode));
    }

    /**
     * Natures that actually carry money, so callers can skip the empty ones
     * instead of printing a row of zeroes for every possible activity.
     *
     * @return list<ActivityNature>
     */
    public function activeNatures(): array
    {
        $natures = [];

        foreach (ActivityNature::cases() as $nature) {
            if (! $this->forNature($nature)->isZero()) {
                $natures[] = $nature;
            }
        }

        return $natures;
    }

    public function isEmpty(): bool
    {
        return $this->total()->isZero();
    }

    public function hasForeignCurrencies(): bool
    {
        return [] !== $this->foreignCurrencies;
    }
}
