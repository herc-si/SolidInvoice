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

namespace Augias\ElectronicInvoicingBundle\Manager;

use Augias\CoreBundle\Entity\Company;
use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceReceipt;

interface ElectronicInvoiceReceiptManagerInterface
{
    /**
     * True only when $company has an active provider that also implements
     * {@see \Augias\ElectronicInvoicingBundle\Provider\ElectronicInvoiceReceiverInterface} —
     * gates both the scheduled import and the "Received Invoices" menu entry.
     */
    public function isReceivingEnabled(Company $company): bool;

    /**
     * Imports every invoice the active provider reports as received since the
     * last import, downloading and storing each one's document locally.
     * Idempotent: already-imported invoices (by provider + external reference)
     * are skipped, so this is safe to call repeatedly (e.g. from a cron task).
     *
     * @return list<ElectronicInvoiceReceipt> the newly imported receipts
     */
    public function importNew(Company $company): array;
}
