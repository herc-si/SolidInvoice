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

namespace SolidInvoice\ElectronicInvoicingBundle\Manager;

use LogicException;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceSubmission;
use SolidInvoice\InvoiceBundle\Entity\Invoice;

interface ElectronicInvoiceManagerInterface
{
    /**
     * Electronic invoicing only applies to French B2B clients with a SIRET —
     * not every client — so this must be true before showing the manual send
     * button or triggering the automatic send on publish.
     */
    public function isEligible(Invoice $invoice): bool;

    /**
     * @throws LogicException if no active provider is configured, or it is unknown
     */
    public function send(Invoice $invoice): ElectronicInvoiceSubmission;
}
