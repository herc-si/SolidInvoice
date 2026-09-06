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

namespace Augias\BillBundle\Enum;

use Augias\CoreBundle\Enum\HasStatusLabel;

/**
 * Mirrors {@see \Augias\InvoiceBundle\Enum\InvoiceStatus}'s shape, minus
 * the states that only make sense for a document this company sends out
 * (`New` pre-send, `Active` for recurring). A Bill is either typed in
 * directly (starts `Draft`, so typos don't count as a real payable until
 * confirmed) or created from an already-final received document (starts
 * `Pending` directly). There's no `PartiallyPaid` place either — like
 * Invoice, the paid amount is tracked numerically ({@see \Augias\BillBundle\Entity\Bill::getBalance()}),
 * not as a separate workflow state.
 */
enum BillStatus: string implements HasStatusLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
            self::Archived => 'Archived',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'yellow',
            self::Paid => 'green',
            self::Overdue => 'red',
            self::Cancelled => 'dark',
            self::Archived => 'purple',
        };
    }
}
