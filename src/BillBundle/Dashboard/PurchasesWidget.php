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

namespace Augias\BillBundle\Dashboard;

use Augias\BillBundle\Entity\Bill;
use Augias\BillBundle\Enum\BillStatus;
use Augias\BillBundle\Repository\BillRepository;
use Augias\DashboardBundle\Attribute\AsDashboardWidget;
use Augias\DashboardBundle\Enum\WidgetWidth;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Widgets\WidgetInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * What the company owes its suppliers, and which of it is late.
 *
 * The dashboard has been entirely about money coming in. A freelancer with an
 * overdue purchase invoice is in the same trouble as one with an overdue sales
 * invoice, only facing the other way, and the page that answers "where do I
 * stand" has had nothing to say about it.
 *
 * Money owed is never summed across currencies — a bill is paid in the currency
 * it was issued in, and an exchange rate invented on a dashboard would be one
 * the books never recorded.
 *
 * @see \Augias\BillBundle\Tests\Dashboard\PurchasesWidgetTest
 */
#[AsDashboardWidget(
    id: 'purchases',
    label: 'dashboard.widget.purchases',
    icon: 'tabler:receipt',
    zone: WidgetZone::LeftColumn,
    // Below the sales side. Being owed money is the more urgent half of the
    // page for most of this application's users, and this sits under it rather
    // than competing with it.
    priority: 90,
    width: WidgetWidth::Full,
)]
final readonly class PurchasesWidget implements WidgetInterface
{
    /**
     * How many bills the card lists before it defers to the purchases page.
     */
    private const int ROWS_SHOWN = 5;

    private ObjectManager $manager;

    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManager();
    }

    /**
     * Always applies. A company with no purchase invoices still wants to be
     * told that is what it has.
     */
    public function supports(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        /** @var BillRepository $bills */
        $bills = $this->manager->getRepository(Bill::class);

        $unpaid = $bills->getUnpaidBills(self::ROWS_SHOWN);

        return [
            'outstanding' => $bills->getOutstandingByCurrency(),
            'bills' => $unpaid,
            // The uncapped count, so the card can say "5 of 12" rather than
            // rendering the capped list as if it were the whole truth.
            'unpaidTotal' => $bills->countByStatus(BillStatus::Pending) + $bills->countByStatus(BillStatus::Overdue),
            'overdueTotal' => $bills->countByStatus(BillStatus::Overdue),
            'hasBills' => [] !== $unpaid,
        ];
    }

    public function getTemplate(): string
    {
        return '@AugiasBill/Widget/purchases.html.twig';
    }
}
