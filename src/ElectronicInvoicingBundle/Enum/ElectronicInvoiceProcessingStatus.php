<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ElectronicInvoicingBundle\Enum;

use SolidInvoice\CoreBundle\Enum\HasStatusLabel;

/**
 * SolidInvoice's own normalization of a provider's raw, provider-specific
 * submission/status-code vocabulary (e.g. SUPER PDP's `fr:205`/`api:rejected`
 * codes) into the three outcomes that matter for display and notifications.
 *
 * This is deliberately separate from {@see \SolidInvoice\InvoiceBundle\Enum\InvoiceStatus}:
 * the invoice's own workflow tracks the business document lifecycle (draft,
 * sent, paid, ...), while this tracks the transmission's own lifecycle on the
 * electronic-invoicing platform. Neither drives the other automatically.
 */
enum ElectronicInvoiceProcessingStatus: string implements HasStatusLabel
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Accepted => 'green',
            self::Rejected => 'red',
        };
    }
}
