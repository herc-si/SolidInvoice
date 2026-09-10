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

namespace Augias\ElectronicInvoicingBundle\Dashboard;

use Augias\DashboardBundle\Attribute\AsDashboardWidget;
use Augias\DashboardBundle\Enum\WidgetWidth;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Widgets\WidgetInterface;
use Augias\ElectronicInvoicingBundle\Provider\ElectronicInvoiceProviderRegistry;
use Augias\ElectronicInvoicingBundle\Repository\ElectronicInvoiceReceiptRepository;

/**
 * Invoices that arrived electronically and have not been turned into purchase
 * invoices yet.
 *
 * These land in the background — a command polls the provider — so nothing
 * tells the user they are there. They are an inbox, and an inbox nobody is
 * shown is a pile of unpaid bills waiting to become overdue ones.
 *
 * "Not dealt with" is the absence of a Bill pointing at the receipt; there is
 * no flag on the receipt itself. That is the repository's rule and this card
 * inherits it, so the count here and the badge on the purchases page cannot
 * disagree.
 *
 * @see \Augias\ElectronicInvoicingBundle\Tests\Dashboard\IncomingInvoicesWidgetTest
 */
#[AsDashboardWidget(
    id: 'einvoicing_incoming',
    label: 'dashboard.widget.einvoicing_incoming',
    icon: 'tabler:inbox',
    zone: WidgetZone::RightColumn,
    // Above the quick actions: this is work that arrived on its own, and it is
    // the half of electronic invoicing with something for the user to do.
    priority: 115,
    width: WidgetWidth::Full,
)]
final readonly class IncomingInvoicesWidget implements WidgetInterface
{
    private const int ROWS_SHOWN = 5;

    public function __construct(
        private ElectronicInvoiceProviderRegistry $registry,
        private ElectronicInvoiceReceiptRepository $receipts,
    ) {
    }

    /**
     * Nothing to say for a company that has not switched electronic invoicing
     * on. Checked before getData(), so the queries below never run for one, and
     * the picker does not offer a card that would only ever be empty.
     */
    public function supports(): bool
    {
        return $this->registry->hasActiveProvider();
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return [
            'receipts' => $this->receipts->findAwaitingBill(self::ROWS_SHOWN),
            // Uncapped, so the card can say "5 of 12" rather than presenting the
            // capped list as the whole of the inbox.
            'awaitingTotal' => $this->receipts->countAwaitingBill(),
        ];
    }

    public function getTemplate(): string
    {
        return '@AugiasElectronicInvoicing/Widget/incoming.html.twig';
    }
}
