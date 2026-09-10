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
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @see \Augias\DashboardBundle\Tests\Widgets\OutstandingTotalWidgetTest
 */
#[AsDashboardWidget(
    id: 'outstanding_total',
    label: 'dashboard.widget.outstanding_total',
    icon: 'tabler:file-invoice',
    zone: WidgetZone::Top,
    priority: 240,
    width: WidgetWidth::Quarter,
)]
final readonly class OutstandingTotalWidget implements WidgetInterface
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
            'totalOutstanding' => $invoiceRepository->getTotalOutstandingByCurrency(),
            'defaultCurrency' => $this->defaultCurrency->code(),
        ];
    }

    /**
     * Nothing to gate on: every account has invoices, even if the answer is zero.
     */
    public function supports(): bool
    {
        return true;
    }

    public function getTemplate(): string
    {
        return '@AugiasDashboard/Widget/stat_outstanding.html.twig';
    }
}
