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

use Augias\DashboardBundle\Attention\AttentionSourceInterface;
use Augias\DashboardBundle\Attribute\AsDashboardWidget;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Entity\RecurringInvoice;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Augias\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Augias\QuoteBundle\Entity\Quote;
use Augias\QuoteBundle\Enum\QuoteStatus;
use Augias\QuoteBundle\Repository\QuoteRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @see \Augias\DashboardBundle\Tests\Widgets\AttentionRequiredWidgetTest
 */
#[AsDashboardWidget(
    id: 'attention_required',
    label: 'dashboard.widget.attention_required',
    icon: 'tabler:alert-triangle',
    zone: WidgetZone::LeftColumn,
    priority: 120,
)]
final readonly class AttentionRequiredWidget implements WidgetInterface
{
    /**
     * How many rows each section renders before it is truncated.
     */
    private const int SECTION_LIMIT = 5;

    private const int UPCOMING_RECURRING_LIMIT = 3;

    private const int UPCOMING_RECURRING_DAYS = 7;

    private ObjectManager $manager;

    /**
     * @param iterable<AttentionSourceInterface> $sources sections other bundles contribute
     */
    public function __construct(
        ManagerRegistry $registry,
        private iterable $sources = [],
        private ?LoggerInterface $logger = null,
    ) {
        $this->manager = $registry->getManager();
    }

    /**
     * Each section returns a capped list plus the uncapped total, so the template
     * can say "5 of 12" instead of rendering the capped count as if it were the
     * whole truth. The totals are COUNT queries, so no extra rows are hydrated.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->manager->getRepository(Invoice::class);
        /** @var QuoteRepository $quoteRepository */
        $quoteRepository = $this->manager->getRepository(Quote::class);
        /** @var RecurringInvoiceRepository $recurringRepository */
        $recurringRepository = $this->manager->getRepository(RecurringInvoice::class);

        $overdueInvoices = $invoiceRepository->getOverdueInvoices(self::SECTION_LIMIT);
        $draftInvoices = $invoiceRepository->getDraftInvoices(self::SECTION_LIMIT);
        $pendingQuotes = $quoteRepository->getPendingQuotes(self::SECTION_LIMIT);
        $upcomingRecurring = $recurringRepository->getUpcomingRecurringInvoices(
            self::UPCOMING_RECURRING_DAYS,
            self::UPCOMING_RECURRING_LIMIT
        );

        $sections = $this->contributedSections();

        return [
            'sections' => $sections,
            'overdueInvoices' => $overdueInvoices,
            'overdueInvoicesTotal' => $invoiceRepository->getCountByStatus(InvoiceStatus::Overdue),
            'draftInvoices' => $draftInvoices,
            'draftInvoicesTotal' => $invoiceRepository->getCountByStatus(InvoiceStatus::Draft),
            'pendingQuotes' => $pendingQuotes,
            'pendingQuotesTotal' => $quoteRepository->getTotalQuotes(QuoteStatus::Pending),
            'upcomingRecurring' => $upcomingRecurring,
            // No repository method counts upcoming recurring invoices using the
            // same window as getUpcomingRecurringInvoices(), and the existing
            // getUpcomingCount() both hydrates every active recurring invoice and
            // counts a different thing (actual next run date). Null means "total
            // unknown" so the template omits the count rather than inventing one.
            'upcomingRecurringTotal' => null,
            'hasItems' => [] !== $overdueInvoices || [] !== $draftInvoices || [] !== $pendingQuotes || [] !== $upcomingRecurring || [] !== $sections,
        ];
    }

    /**
     * The extra sections, rendered by whoever contributed them.
     *
     * A source is resolved to a template and its data here rather than in Twig,
     * so getData() is called once and the failure of one contributor cannot take
     * the invoices down with it: this card is the invoice one first, and an
     * accounting outage has no business emptying it.
     *
     * @return list<array{template: string, data: array<string, mixed>}>
     */
    private function contributedSections(): array
    {
        $sections = [];

        foreach ($this->sources as $source) {
            try {
                if ($source->supports() && $source->hasItems()) {
                    $sections[] = ['template' => $source->getTemplate(), 'data' => $source->getData()];
                }
            } catch (Throwable $e) {
                $this->logger?->error('Unable to build a dashboard attention section', [
                    'source' => $source::class,
                    'exception' => $e,
                ]);
            }
        }

        return $sections;
    }

    /**
     * Always applies. An account with nothing overdue still wants to be told so.
     */
    public function supports(): bool
    {
        return true;
    }

    public function getTemplate(): string
    {
        return '@AugiasDashboard/Widget/attention_required.html.twig';
    }
}
