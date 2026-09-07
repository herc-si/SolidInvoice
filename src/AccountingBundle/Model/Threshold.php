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
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Money;

/**
 * One turnover limit a company is measured against, resolved for a given date.
 *
 * The amount is minor units so it can be compared with ledger sums without
 * conversion, and it is carried by value rather than looked up on demand:
 * thresholds are revised by finance acts, and a figure quoted in an alert or
 * frozen into a declaration has to stay the figure that was actually applied.
 */
final readonly class Threshold
{
    /**
     * @param string              $key      stable identifier, e.g. `micro_ceiling.services_bic`
     * @param string              $labelKey translation key for display
     * @param BigInteger          $amount   minor units
     * @param ActivityNature|null $nature   null when the limit spans all activities
     * @param bool                $prorated whether $amount was scaled down for a partial first year
     */
    public function __construct(
        public string $key,
        public string $labelKey,
        public BigInteger $amount,
        public string $currencyCode,
        public ?ActivityNature $nature = null,
        public bool $prorated = false,
    ) {
    }

    public function getMoney(): Money
    {
        return new Money((string) $this->amount, new Currency($this->currencyCode));
    }

    /**
     * How much of the limit is used up, as a percentage. Returns 0 for a
     * threshold of zero rather than dividing — a disabled or unknown limit
     * should read as untouched, not as infinitely exceeded.
     */
    public function usageRatio(BigInteger $turnover): BigDecimal
    {
        if ($this->amount->isZero()) {
            return BigDecimal::zero();
        }

        return $turnover->toBigDecimal()
            ->dividedBy($this->amount->toBigDecimal(), 4, RoundingMode::HalfUp)
            ->multipliedBy(100);
    }

    public function isExceededBy(BigInteger $turnover): bool
    {
        return ! $this->amount->isZero() && $turnover->isGreaterThan($this->amount);
    }

    /**
     * Scale the limit down to the part of the year the company actually
     * traded — the prorata temporis rule that applies to a first, incomplete
     * year of activity.
     */
    public function proratedTo(int $daysTraded, int $daysInYear): self
    {
        if ($daysInYear <= 0 || $daysTraded >= $daysInYear) {
            return $this;
        }

        $prorated = $this->amount->toBigDecimal()
            ->multipliedBy($daysTraded)
            ->dividedBy($daysInYear, 0, RoundingMode::HalfUp)
            ->toBigInteger();

        return new self($this->key, $this->labelKey, $prorated, $this->currencyCode, $this->nature, true);
    }
}
