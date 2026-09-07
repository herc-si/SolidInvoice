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

namespace Augias\CatalogBundle\Enum;

/**
 * How a catalogue entry is sold. Copied onto the invoice line so a later change
 * here never rewrites what an already-issued document said.
 */
enum ProductUnit: string
{
    case Unit = 'unit';

    case Hour = 'hour';

    case Day = 'day';

    case Month = 'month';

    case Kilogram = 'kilogram';

    case Litre = 'litre';

    case Metre = 'metre';

    case FlatRate = 'flat_rate';

    public function getLabel(): string
    {
        return 'catalog.unit.' . $this->value;
    }
}
