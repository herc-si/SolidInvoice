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
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * @see \Augias\DashboardBundle\Tests\Widgets\InvoiceDistributionWidgetTest
 */
#[AsDashboardWidget(
    id: 'invoice_distribution',
    label: 'dashboard.widget.invoice_distribution',
    icon: 'tabler:chart-donut',
    zone: WidgetZone::RightColumn,
    priority: 10,
)]
final readonly class InvoiceDistributionWidget implements WidgetInterface
{
    private ObjectManager $manager;

    /**
     * Status colors and translation keys matching the design system.
     *
     * @var array<string, array{color: string, label: string}>
     */
    private const array STATUS_CONFIG = [
        InvoiceStatus::Paid->value => ['color' => 'rgb(16, 185, 129)', 'label' => 'dashboard.distribution.status.paid'],
        InvoiceStatus::Pending->value => ['color' => 'rgb(59, 130, 246)', 'label' => 'dashboard.distribution.status.pending'],
        InvoiceStatus::Overdue->value => ['color' => 'rgb(239, 68, 68)', 'label' => 'dashboard.distribution.status.overdue'],
        InvoiceStatus::Draft->value => ['color' => 'rgb(148, 163, 184)', 'label' => 'dashboard.distribution.status.draft'],
        InvoiceStatus::Cancelled->value => ['color' => 'rgb(100, 116, 139)', 'label' => 'dashboard.distribution.status.cancelled'],
    ];

    public function __construct(
        ManagerRegistry $registry,
        private ChartBuilderInterface $chartBuilder,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
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

        try {
            $statusCounts = $invoiceRepository->getCountByStatusAll();
        } catch (DBALException | ORMException $e) {
            $this->logger->error('Unable to load the invoice distribution data', ['exception' => $e]);

            // A failed query must not render as an empty chart: "no invoices yet"
            // and "we could not read your invoices" are different statements.
            return [
                'chart' => null,
                'hasError' => true,
                'hasData' => false,
                'total' => 0,
            ];
        }

        // Filter to only include statuses we want to display
        $relevantStatuses = [
            InvoiceStatus::Paid,
            InvoiceStatus::Pending,
            InvoiceStatus::Overdue,
            InvoiceStatus::Draft,
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($relevantStatuses as $status) {
            $count = $statusCounts[$status->value] ?? 0;
            if ($count > 0 || $status === InvoiceStatus::Pending) {
                $config = self::STATUS_CONFIG[$status->value];
                $labels[] = $this->translator->trans($config['label']);
                $data[] = $count;
                $colors[] = $config['color'];
            }
        }

        $hasData = array_sum($data) > 0;

        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                    'hoverOffset' => 8,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '70%',
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 16,
                        'color' => '#475569',
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(30, 41, 59, 0.9)',
                    'titleColor' => '#fff',
                    'bodyColor' => '#fff',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
        ]);

        return [
            'chart' => $chart,
            'hasError' => false,
            'hasData' => $hasData,
            'total' => array_sum($data),
        ];
    }

    /**
     * Always applies; an account with no invoices gets the empty state.
     */
    public function supports(): bool
    {
        return true;
    }

    public function getTemplate(): string
    {
        return '@AugiasDashboard/Widget/invoice_distribution.html.twig';
    }
}
