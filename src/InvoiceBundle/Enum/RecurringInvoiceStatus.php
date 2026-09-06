<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\InvoiceBundle\Enum;

use Augias\CoreBundle\Enum\HasStatusLabel;

enum RecurringInvoiceStatus: string implements HasStatusLabel
{
    case New = 'new';
    case Active = 'active';
    case Complete = 'complete';
    case Draft = 'draft';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Active => 'Active',
            self::Complete => 'Complete',
            self::Draft => 'Draft',
            self::Paused => 'Paused',
            self::Cancelled => 'Cancelled',
            self::Archived => 'Archived',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::Active => 'green',
            self::Complete => 'teal',
            self::Draft => 'gray',
            self::Paused => 'dark',
            self::Cancelled => 'dark',
            self::Archived => 'purple',
        };
    }
}
