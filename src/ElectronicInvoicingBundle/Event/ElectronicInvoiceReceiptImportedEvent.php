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

namespace Augias\ElectronicInvoicingBundle\Event;

use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceReceipt;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched once per invoice newly pulled from a provider, after the receipt
 * has been flushed so it carries an id. Lets BillBundle file it as a purchase
 * invoice without this bundle needing to know bills exist.
 */
final class ElectronicInvoiceReceiptImportedEvent extends Event
{
    public function __construct(
        public readonly ElectronicInvoiceReceipt $receipt,
    ) {
    }
}
