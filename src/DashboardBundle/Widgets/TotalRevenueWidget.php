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
use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Repository\PaymentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @see \Augias\DashboardBundle\Tests\Widgets\TotalRevenueWidgetTest
 */
#[AsDashboardWidget(
    id: 'total_revenue',
    label: 'dashboard.widget.total_revenue',
    icon: 'tabler:trending-up',
    zone: WidgetZone::Top,
    priority: 210,
    width: WidgetWidth::Quarter,
)]
final readonly class TotalRevenueWidget implements WidgetInterface
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
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository(Payment::class);

        return [
            'totalRevenue' => $paymentRepository->getTotalIncome(),
            'defaultCurrency' => $this->defaultCurrency->code(),
        ];
    }

    /**
     * Nothing to gate on: every account has payments, even if the answer is zero.
     */
    public function supports(): bool
    {
        return true;
    }

    public function getTemplate(): string
    {
        return '@AugiasDashboard/Widget/stat_total_revenue.html.twig';
    }
}
