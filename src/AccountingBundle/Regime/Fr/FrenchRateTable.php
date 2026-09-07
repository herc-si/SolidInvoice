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

namespace Augias\AccountingBundle\Regime\Fr;

use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\PensionFund;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use RuntimeException;
use function is_array;
use function sprintf;
use function usort;

/**
 * Resolves French micro-entreprise rates and ceilings for a given date.
 *
 * The figures live in `Resources/config/fr_micro_rates.php` as dated entries
 * and are read here, never hard-coded. Resolution takes the last entry whose
 * `effective_from` is on or before the date asked about, so a period closed in
 * 2024 keeps being measured against 2024's rules however many finance acts have
 * passed since.
 *
 * A self-hosted install can point this at its own file — see the `rates_file`
 * service argument — which is the escape hatch for a rate change that lands
 * before the next release does.
 *
 * @see \Augias\AccountingBundle\Tests\Regime\Fr\FrenchRateTableTest
 */
final class FrenchRateTable
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    public function __construct(
        private readonly string $ratesFile,
    ) {
    }

    /**
     * Regime ceiling for one activity, in minor units.
     */
    public function microCeiling(ActivityNature $nature, DateTimeImmutable $on): BigInteger
    {
        return $this->thresholdAmount('micro_ceiling', $nature, $on);
    }

    /**
     * VAT franchise threshold — below this, no VAT is charged at all.
     */
    public function vatFranchiseBase(ActivityNature $nature, DateTimeImmutable $on): BigInteger
    {
        return $this->thresholdAmount('vat_franchise_base', $nature, $on);
    }

    /**
     * The higher tolerance threshold. Crossing it makes VAT due, which is the
     * event a micro-entrepreneur most needs warning about.
     */
    public function vatFranchiseTolerance(ActivityNature $nature, DateTimeImmutable $on): BigInteger
    {
        return $this->thresholdAmount('vat_franchise_tolerance', $nature, $on);
    }

    /**
     * Social contribution rate as a percentage. BNC depends on which body
     * collects, so the pension fund has to be passed in.
     */
    public function socialRate(ActivityNature $nature, DateTimeImmutable $on, PensionFund $fund): BigDecimal
    {
        $entry = $this->resolve('contributions', $on);

        $key = match ($nature) {
            ActivityNature::SaleOfGoods => 'sale_of_goods',
            ActivityNature::ServicesBic => 'services_bic',
            ActivityNature::ServicesBnc => $fund === PensionFund::Cipav
                ? 'services_bnc_cipav'
                : 'services_bnc_ssi',
        };

        return $this->rate($entry, 'social', $key);
    }

    /**
     * Contribution à la formation professionnelle.
     */
    public function trainingRate(ActivityNature $nature, DateTimeImmutable $on): BigDecimal
    {
        return $this->rate($this->resolve('contributions', $on), 'training', $nature->value);
    }

    /**
     * Flat-rate income tax, only owed when the company opted into it.
     */
    public function incomeTaxRate(ActivityNature $nature, DateTimeImmutable $on): BigDecimal
    {
        return $this->rate($this->resolve('contributions', $on), 'income_tax', $nature->value);
    }

    /**
     * How much ACRE takes off the social rate, as a percentage of that rate.
     */
    public function acreReductionPercent(DateTimeImmutable $on): BigDecimal
    {
        $entry = $this->resolve('contributions', $on);
        $acre = $entry['acre'] ?? [];

        return BigDecimal::of((string) (is_array($acre) ? ($acre['reduction_percent'] ?? '0') : '0'));
    }

    /**
     * How long the ACRE reduction lasts, in months from the start of activity.
     */
    public function acreDurationMonths(DateTimeImmutable $on): int
    {
        $entry = $this->resolve('contributions', $on);
        $acre = $entry['acre'] ?? [];

        return (int) (is_array($acre) ? ($acre['duration_months'] ?? 0) : 0);
    }

    /**
     * The vintage of the contribution rates used, stored on every declaration
     * so an old one can still be explained.
     */
    public function contributionVersion(DateTimeImmutable $on): string
    {
        return (string) $this->resolve('contributions', $on)['effective_from'];
    }

    public function thresholdVersion(DateTimeImmutable $on): string
    {
        return (string) $this->resolve('thresholds', $on)['effective_from'];
    }

    /**
     * Whether the figures in force on this date have been checked against an
     * official source. Surfaced in the UI so nobody files on the strength of a
     * placeholder.
     */
    public function isVerified(DateTimeImmutable $on): bool
    {
        return (bool) ($this->resolve('contributions', $on)['verified'] ?? false)
            && (bool) ($this->resolve('thresholds', $on)['verified'] ?? false);
    }

    private function thresholdAmount(string $group, ActivityNature $nature, DateTimeImmutable $on): BigInteger
    {
        $entry = $this->resolve('thresholds', $on);
        $group = $entry[$group] ?? [];

        if (! is_array($group) || ! isset($group[$nature->value])) {
            return BigInteger::zero();
        }

        return BigInteger::of((string) $group[$nature->value]);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function rate(array $entry, string $group, string $key): BigDecimal
    {
        $rates = $entry[$group] ?? [];

        if (! is_array($rates) || ! isset($rates[$key])) {
            return BigDecimal::zero();
        }

        return BigDecimal::of((string) $rates[$key]);
    }

    /**
     * The last entry effective on or before $on.
     *
     * @return array<string, mixed>
     */
    private function resolve(string $section, DateTimeImmutable $on): array
    {
        $entries = $this->load()[$section] ?? [];

        if (! is_array($entries) || [] === $entries) {
            throw new RuntimeException(sprintf('The French rate table has no "%s" section.', $section));
        }

        $date = $on->format('Y-m-d');
        $match = null;

        foreach ($entries as $entry) {
            if ((string) $entry['effective_from'] <= $date) {
                $match = $entry;
            }
        }

        // Nothing in force yet — a date before the earliest entry. Falling back
        // to the oldest set beats throwing: it is the closest thing to right,
        // and a company backfilling old payments should not hit a hard error.
        return $match ?? $entries[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        if (null === $this->data) {
            /** @var array<string, mixed> $data */
            $data = require $this->ratesFile;

            foreach (['thresholds', 'contributions'] as $section) {
                if (isset($data[$section]) && is_array($data[$section])) {
                    usort(
                        $data[$section],
                        static fn (array $a, array $b): int => (string) $a['effective_from'] <=> (string) $b['effective_from'],
                    );
                }
            }

            $this->data = $data;
        }

        return $this->data;
    }
}
