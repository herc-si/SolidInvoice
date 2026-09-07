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

use Augias\CoreBundle\Enum\HasStatusLabel;

/**
 * Where a turnover declaration stands. Augias never files anything: the user
 * copies the figures onto the tax authority's own site and comes back to
 * record that they did, along with the reference they were given. So
 * {@see self::Submitted} is a statement by the user, not something the
 * application can observe.
 */
enum DeclarationStatus: string implements HasStatusLabel
{
    /** Computed but still moving — the underlying period is open. */
    case Draft = 'draft';

    /** The period is closed and the figures are final; nothing left but to file it. */
    case Ready = 'ready';

    /** The user has filed it and recorded the reference. */
    case Submitted = 'submitted';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Ready => 'Ready to file',
            self::Submitted => 'Filed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Ready => 'yellow',
            self::Submitted => 'green',
        };
    }

    public function translationKey(): string
    {
        return 'accounting.declaration_status.' . $this->value;
    }
}
