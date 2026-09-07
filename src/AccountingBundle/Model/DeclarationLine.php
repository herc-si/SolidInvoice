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
 * One computed line of a declaration: a base, a rate, and what the two produce.
 *
 * Every charge is shown this way rather than rolled into a single total, for
 * two reasons. The user has to type these numbers into the tax authority's own
 * form, box by box; and when a published rate turns out to be wrong or out of
 * date, they need to see which one, and override it — hence
 * {@see $rateOverridable}.
 */
final readonly class DeclarationLine
{
    public const string KIND_CONTRIBUTION = 'contribution';

    public const string KIND_LEVY = 'levy';

    public const string KIND_INCOME_TAX = 'income_tax';

    /**
     * @param string     $key      stable identifier, e.g. `social.services_bic`
     * @param string     $labelKey translation key
     * @param BigInteger $base     minor units the rate is applied to
     * @param BigDecimal $rate     percentage, e.g. `21.2` for 21.2%
     * @param BigInteger $amount   minor units
     */
    public function __construct(
        public string $key,
        public string $labelKey,
        public string $kind,
        public BigInteger $base,
        public BigDecimal $rate,
        public BigInteger $amount,
        public string $currencyCode,
        public ?ActivityNature $nature = null,
        public bool $rateOverridable = true,
    ) {
    }

    /**
     * Build a line from a base and a rate, doing the rounding in one place.
     * Half-up on the minor unit, which is how contribution bases are rounded in
     * practice.
     */
    public static function fromRate(
        string $key,
        string $labelKey,
        string $kind,
        BigInteger $base,
        BigDecimal $rate,
        string $currencyCode,
        ?ActivityNature $nature = null,
        bool $rateOverridable = true,
    ): self {
        $amount = $base->toBigDecimal()
            ->multipliedBy($rate)
            ->dividedBy(100, 0, RoundingMode::HalfUp)
            ->toBigInteger();

        return new self($key, $labelKey, $kind, $base, $rate, $amount, $currencyCode, $nature, $rateOverridable);
    }

    public function getMoney(): Money
    {
        return new Money((string) $this->amount, new Currency($this->currencyCode));
    }

    public function getBaseMoney(): Money
    {
        return new Money((string) $this->base, new Currency($this->currencyCode));
    }

    /**
     * The same line recomputed at a different rate — what an override produces.
     */
    public function withRate(BigDecimal $rate): self
    {
        return self::fromRate(
            $this->key,
            $this->labelKey,
            $this->kind,
            $this->base,
            $rate,
            $this->currencyCode,
            $this->nature,
            $this->rateOverridable,
        );
    }

    /**
     * Shape stored on {@see \Augias\AccountingBundle\Entity\Declaration::$lines}.
     * Amounts are strings: they are minor units of arbitrary size and must not
     * pass through a JSON float.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'labelKey' => $this->labelKey,
            'kind' => $this->kind,
            'base' => (string) $this->base,
            'rate' => (string) $this->rate,
            'amount' => (string) $this->amount,
            'currencyCode' => $this->currencyCode,
            'nature' => $this->nature?->value,
            'rateOverridable' => $this->rateOverridable,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['key'],
            (string) $data['labelKey'],
            (string) $data['kind'],
            BigInteger::of((string) $data['base']),
            BigDecimal::of((string) $data['rate']),
            BigInteger::of((string) $data['amount']),
            (string) $data['currencyCode'],
            null === ($data['nature'] ?? null) ? null : ActivityNature::from((string) $data['nature']),
            (bool) ($data['rateOverridable'] ?? true),
        );
    }
}
