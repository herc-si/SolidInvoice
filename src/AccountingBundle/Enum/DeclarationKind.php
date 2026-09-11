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
 * Which return a declaration is.
 *
 * A period can owe more than one, and they go to different places on different
 * forms: a micro-entrepreneur past the franchise threshold declares turnover to
 * URSSAF *and* VAT to the tax office for the very same quarter. Modelling VAT as
 * a rival regime would have made those two mutually exclusive, which they are
 * not.
 */
enum DeclarationKind: string
{
    /** Turnover declared to the social security body, with what it charges on it. */
    case SocialContributions = 'social_contributions';

    /** VAT collected less VAT deducted, for the tax office. */
    case Vat = 'vat';

    public function translationKey(): string
    {
        return 'accounting.declaration.kind.' . $this->value;
    }

    public function icon(): string
    {
        return match ($this) {
            self::SocialContributions => 'tabler:users-group',
            self::Vat => 'tabler:receipt-tax',
        };
    }
}
