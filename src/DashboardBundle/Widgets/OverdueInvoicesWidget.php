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
use Augias\DashboardBundle\Enum\WidgetWidth;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @see \Augias\DashboardBundle\Tests\Widgets\OverdueInvoicesWidgetTest
 */
#[AsDashboardWidget(
    id: 'overdue_invoices',
    label: 'dashboard.widget.overdue_invoices',
    icon: 'tabler:alert-triangle',
    zone: WidgetZone::Top,
    priority: 230,
    width: WidgetWidth::Quarter,
)]
final readonly class OverdueInvoicesWidget implements WidgetInterface
{
    private ObjectManager $manager;

    public function __construct(
        ManagerRegistry $registry,
        private DefaultCurrency $defaultCurrency,
    ) {
        $this->manager = $registry->getManager();
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->manager->getRepository(Invoice::class);

        return [
            'overdueCount' => $invoiceRepository->getCountByStatus(InvoiceStatus::Overdue),
            'overdueAmount' => $invoiceRepository->getOverdueAmountByCurrency(),
            'defaultCurrency' => $this->defaultCurrency->code(),
        ];
    }

    /**
     * Shown at zero as well. "Nothing is overdue" is an answer the user came for,
     * and a tile that vanishes when the news is good leaves them wondering
     * whether it was ever there.
     */
    public function supports(): bool
    {
        return true;
    }

    public function getTemplate(): string
    {
        return '@AugiasDashboard/Widget/stat_overdue.html.twig';
    }
}
