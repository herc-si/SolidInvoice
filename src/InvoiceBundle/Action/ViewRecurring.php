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

namespace Augias\InvoiceBundle\Action;

use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Entity\RecurringInvoice;
use Augias\InvoiceBundle\Enum\RecurringInvoiceStatus;
use Augias\InvoiceBundle\Recurring\RecurringSchedule;
use Carbon\CarbonInterface;
use Symfony\Bridge\Twig\Attribute\Template;

final readonly class ViewRecurring
{
    public function __construct(
        private RecurringSchedule $recurringSchedule
    ) {
    }

    /**
     * @return array{invoice: RecurringInvoice, nextOccurrences: array<CarbonInterface>, generatedInvoices: array<int, Invoice>, totalGenerated: int}
     */
    #[Template('@AugiasInvoice/Default/view_recurring.html.twig')]
    public function __invoke(RecurringInvoice $invoice): array
    {
        // Get next 5 upcoming occurrences for active invoices
        $nextOccurrences = [];
        if ($invoice->getStatus() === RecurringInvoiceStatus::Active->value) {
            $nextOccurrences = iterator_to_array(
                $this->recurringSchedule->getNextOccurrences($invoice->getRecurringOptions(), 5)
            );
        }

        // Get last 5 generated invoices (collection is ordered by created DESC via OrderBy annotation)
        $invoicesCollection = $invoice->getInvoices();
        $totalGenerated = $invoicesCollection->count();
        $generatedInvoices = $invoicesCollection->slice(0, 5);

        return [
            'invoice' => $invoice,
            'nextOccurrences' => $nextOccurrences,
            'generatedInvoices' => $generatedInvoices,
            'totalGenerated' => $totalGenerated,
        ];
    }
}
