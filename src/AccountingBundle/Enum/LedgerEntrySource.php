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
 * Where a ledger entry came from. Together with the source record's id this
 * forms the uniqueness key that keeps the automatic feeders idempotent — the
 * same captured payment can be flushed any number of times without ever
 * producing a second entry.
 *
 * {@see self::Manual} entries have no source id: nothing in the application
 * produced them, so nothing can deduplicate them either.
 */
enum LedgerEntrySource: string
{
    case InvoicePayment = 'invoice_payment';

    case BillPayment = 'bill_payment';

    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::InvoicePayment => 'Invoice payment',
            self::BillPayment => 'Supplier payment',
            self::Manual => 'Manual entry',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::InvoicePayment => 'accounting.entry.source.invoice_payment',
            self::BillPayment => 'accounting.entry.source.bill_payment',
            self::Manual => 'accounting.entry.source.manual',
        };
    }

    /**
     * Whether entries from this source are maintained by the application. Only
     * manual entries may have their amount and date edited freely; the rest
     * mirror a payment record and would silently drift out of sync with it.
     */
    public function isAutomatic(): bool
    {
        return $this !== self::Manual;
    }
}
