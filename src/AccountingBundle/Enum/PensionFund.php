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
 * Which body collects a French micro-entrepreneur's pension contributions.
 *
 * Specific to the French regime — it is read through
 * {@see \Augias\AccountingBundle\Model\AccountingProfile::option()} and never
 * appears in the shared profile — but it lives here with the other enums for
 * consistency with the rest of the codebase.
 *
 * It matters because the two carry different contribution rates for the same
 * BNC turnover, and a company cannot tell which applies from its activity
 * alone: it depends on when it registered.
 */
enum PensionFund: string
{
    /** Sécurité sociale des indépendants — the default for new registrations. */
    case Ssi = 'ssi';

    /** Caisse interprofessionnelle de prévoyance et d'assurance vieillesse. */
    case Cipav = 'cipav';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ssi => 'SSI',
            self::Cipav => 'CIPAV',
        };
    }

    public function translationKey(): string
    {
        return 'accounting.pension_fund.' . $this->value;
    }

    /**
     * @return array<string, string> translation key => value, for form choices
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
