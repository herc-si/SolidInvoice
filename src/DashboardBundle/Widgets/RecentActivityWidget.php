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

namespace Augias\DashboardBundle\Widgets;

use Augias\DashboardBundle\Attribute\AsDashboardWidget;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Repository\PaymentRepository;
use Augias\QuoteBundle\Entity\Quote;
use Augias\QuoteBundle\Repository\QuoteRepository;
use DateTimeInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @see \Augias\DashboardBundle\Tests\Widgets\RecentActivityWidgetTest
 */
#[AsDashboardWidget(
    id: 'recent_activity',
    label: 'dashboard.widget.recent_activity',
    icon: 'tabler:history',
    zone: WidgetZone::RightColumn,
    priority: 50,
)]
final readonly class RecentActivityWidget implements WidgetInterface
{
    private ObjectManager $manager;

    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManager();
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->manager->getRepository(Invoice::class);
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository(Payment::class);
        /** @var QuoteRepository $quoteRepository */
        $quoteRepository = $this->manager->getRepository(Quote::class);

        // Fetch different activity types
        $recentPayments = $paymentRepository->getRecentPayments(5);
        $recentlySentInvoices = $invoiceRepository->getRecentlySentInvoices(5);
        $recentlyRespondedQuotes = $quoteRepository->getRecentlyRespondedQuotes(5);
        $recentRecurringInvoices = $invoiceRepository->getRecentRecurringGeneratedInvoices(5);

        // Merge and sort by date
        $activities = [];

        // Payments received
        foreach ($recentPayments as $payment) {
            $activities[] = [
                'type' => 'payment',
                'date' => $payment['created'],
                'id' => $payment['id'],
                'invoiceId' => $payment['invoice'],
                'invoiceUlid' => $payment['invoice_ulid'],
                'client' => $payment['client'],
                'clientId' => $payment['client_id'],
                'amount' => $payment['amount'],
                'currency' => $payment['currencyCode'],
                'status' => $payment['status'],
                'method' => $payment['method'],
            ];
        }

        // Invoices sent (pending status)
        foreach ($recentlySentInvoices as $invoice) {
            $activities[] = [
                'type' => 'invoice_sent',
                'date' => $invoice->getUpdated() ?? $invoice->getCreated(),
                'id' => $invoice->getId(),
                'invoiceId' => $invoice->getInvoiceId(),
                'client' => $invoice->getClient()?->getName(),
                'clientId' => $invoice->getClient()?->getId(),
                'amount' => $invoice->getTotal(),
                'currency' => $invoice->getClient()?->getCurrency(),
                'status' => $invoice->getStatus(),
            ];
        }

        // Quote responses (accepted/declined)
        foreach ($recentlyRespondedQuotes as $quote) {
            $activities[] = [
                'type' => 'quote_' . $quote->getStatus()?->value,
                'date' => $quote->getUpdated() ?? $quote->getCreated(),
                'id' => $quote->getId(),
                'quoteId' => $quote->getQuoteId(),
                'client' => $quote->getClient()?->getName(),
                'clientId' => $quote->getClient()?->getId(),
                'amount' => $quote->getTotal(),
                'currency' => $quote->getClient()?->getCurrency(),
                'status' => $quote->getStatus(),
            ];
        }

        // Recurring invoices generated
        foreach ($recentRecurringInvoices as $invoice) {
            $activities[] = [
                'type' => 'recurring_generated',
                'date' => $invoice->getCreated(),
                'id' => $invoice->getId(),
                'invoiceId' => $invoice->getInvoiceId(),
                'client' => $invoice->getClient()?->getName(),
                'clientId' => $invoice->getClient()?->getId(),
                'amount' => $invoice->getTotal(),
                'currency' => $invoice->getClient()?->getCurrency(),
                'status' => $invoice->getStatus(),
            ];
        }

        // Sort by date descending
        usort($activities, static function (array $a, array $b): int {
            $dateOne = $a['date'] instanceof DateTimeInterface ? $a['date'] : null;
            $dateTwo = $b['date'] instanceof DateTimeInterface ? $b['date'] : null;

            if (! $dateOne instanceof DateTimeInterface && ! $dateTwo instanceof DateTimeInterface) {
                return 0;
            }

            if (! $dateOne instanceof DateTimeInterface) {
                return 1;
            }

            if (! $dateTwo instanceof DateTimeInterface) {
                return -1;
            }

            return $dateTwo <=> $dateOne;
        });

        // Limit to 10 items
        $activities = array_slice($activities, 0, 10);

        return [
            'activities' => $activities,
            'hasActivities' => $activities !== [],
        ];
    }

    /**
     * Always applies; the empty feed is itself an answer.
     */
    public function supports(): bool
    {
        return true;
    }

    public function getTemplate(): string
    {
        return '@AugiasDashboard/Widget/recent_activity.html.twig';
    }
}
