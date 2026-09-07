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
 * Only two states, and the transition between them goes one way. While a
 * period is {@see self::Open} its entries are freely editable — that is the
 * only workable way to fix a typo. {@see self::Closed} seals them: sequence
 * numbers are assigned, the hash chain is computed and no entry in the period
 * may be altered or removed again. A mistake found afterwards is corrected by
 * a reversing entry in an open period, the way accounting has always done it.
 */
enum PeriodStatus: string implements HasStatusLabel
{
    case Open = 'open';

    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Closed => 'Closed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'yellow',
            self::Closed => 'green',
        };
    }

    public function translationKey(): string
    {
        return 'accounting.period_status.' . $this->value;
    }
}
