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

/**
 * The kind of activity a revenue entry comes from. This is the axis every
 * French micro-entreprise figure is split along: the regime ceiling, the
 * social-contribution rate, the flat-rate allowance and the optional
 * income-tax payment all differ per nature, and a company may well have two
 * of them at once (a "mixed" activity), which is why it is recorded per entry
 * rather than once on the company.
 *
 * No rate or ceiling is attached here on purpose — those change yearly and
 * live in the dated table read by
 * {@see \Augias\AccountingBundle\Regime\Fr\FrenchRateTable}.
 */
enum ActivityNature: string
{
    /** Vente de marchandises, fourniture de denrées, fourniture de logement — BIC. */
    case SaleOfGoods = 'sale_of_goods';

    /** Prestations de services relevant des BIC. */
    case ServicesBic = 'services_bic';

    /** Prestations de services relevant des BNC (professions libérales). */
    case ServicesBnc = 'services_bnc';

    public function getLabel(): string
    {
        return match ($this) {
            self::SaleOfGoods => 'Sale of goods',
            self::ServicesBic => 'Services (BIC)',
            self::ServicesBnc => 'Services (BNC)',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::SaleOfGoods => 'accounting.activity_nature.sale_of_goods',
            self::ServicesBic => 'accounting.activity_nature.services_bic',
            self::ServicesBnc => 'accounting.activity_nature.services_bnc',
        };
    }

    /**
     * Sale-of-goods activities share a ceiling and a VAT threshold, while both
     * kinds of services share another pair. Several rate lookups key off this
     * grouping rather than the nature itself.
     */
    public function isSaleOfGoods(): bool
    {
        return $this === self::SaleOfGoods;
    }

    /**
     * @return array<string, string> value => translation key, for form choices
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $case) {
            $choices[$case->translationKey()] = $case->value;
        }

        return $choices;
    }
}
