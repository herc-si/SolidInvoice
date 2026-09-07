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

use Brick\Math\BigInteger;
use Money\Currency;
use Money\Money;
use function array_filter;
use function array_map;
use function array_values;
use function in_array;

/**
 * What a regime's contribution calculator produces for one period: the turnover
 * to report and every charge derived from it, itemised.
 *
 * {@see $rateVersion} names the dated rate set that produced the figures, so a
 * declaration filed two years ago can still be explained after the rates have
 * moved on.
 */
final readonly class DeclarationResult
{
    /**
     * @param list<DeclarationLine> $lines
     * @param list<string>          $warnings translation keys for anything the
     *                                        user should check by hand
     */
    public function __construct(
        public BigInteger $turnover,
        public array $lines,
        public string $currencyCode,
        public ?string $rateVersion = null,
        public array $warnings = [],
    ) {
    }

    public function totalContributions(): BigInteger
    {
        return $this->sumOfKinds([DeclarationLine::KIND_CONTRIBUTION, DeclarationLine::KIND_LEVY]);
    }

    public function totalIncomeTax(): BigInteger
    {
        return $this->sumOfKinds([DeclarationLine::KIND_INCOME_TAX]);
    }

    public function totalDue(): BigInteger
    {
        $total = BigInteger::zero();

        foreach ($this->lines as $line) {
            $total = $total->plus($line->amount);
        }

        return $total;
    }

    public function turnoverMoney(): Money
    {
        return new Money((string) $this->turnover, new Currency($this->currencyCode));
    }

    public function totalDueMoney(): Money
    {
        return new Money((string) $this->totalDue(), new Currency($this->currencyCode));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function linesToArray(): array
    {
        return array_map(static fn (DeclarationLine $line): array => $line->toArray(), $this->lines);
    }

    /**
     * @param list<string> $kinds
     */
    private function sumOfKinds(array $kinds): BigInteger
    {
        $total = BigInteger::zero();

        $matching = array_values(array_filter(
            $this->lines,
            static fn (DeclarationLine $line): bool => in_array($line->kind, $kinds, true),
        ));

        foreach ($matching as $line) {
            $total = $total->plus($line->amount);
        }

        return $total;
    }
}
