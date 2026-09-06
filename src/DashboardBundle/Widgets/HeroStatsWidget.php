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

use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Repository\PaymentRepository;
use Augias\SettingsBundle\SystemConfig;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

/**
 * @see \Augias\DashboardBundle\Tests\Widgets\HeroStatsWidgetTest
 */
final readonly class HeroStatsWidget implements WidgetInterface
{
    private ObjectManager $manager;

    public function __construct(
        ManagerRegistry $registry,
        private SystemConfig $systemConfig,
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
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository(Payment::class);

        return [
            'totalOutstanding' => $invoiceRepository->getTotalOutstandingByCurrency(),
            'overdueCount' => $invoiceRepository->getCountByStatus(InvoiceStatus::Overdue),
            'overdueAmount' => $invoiceRepository->getOverdueAmountByCurrency(),
            'paymentsThisMonth' => $paymentRepository->getPaymentsThisMonth(),
            'totalRevenue' => $paymentRepository->getTotalIncome(),
            'defaultCurrency' => $this->defaultCurrency(),
        ];
    }

    /**
     * The currency an empty stat should be denominated in.
     *
     * Every populated branch is denominated in the client's currency, which does
     * not exist when a stat has no rows behind it. The company's configured
     * currency is the only defensible basis in that case, so the widget states it
     * explicitly instead of leaving the template to guess.
     *
     * Null when no currency is configured yet (a fresh install): callers should
     * treat it as "unknown" rather than substituting an arbitrary currency.
     */
    private function defaultCurrency(): ?string
    {
        try {
            return $this->systemConfig->getCurrency()->getCode();
        } catch (RuntimeException) {
            return null;
        }
    }

    public function getTemplate(): string
    {
        return '@AugiasDashboard/Widget/hero_stats.html.twig';
    }
}
