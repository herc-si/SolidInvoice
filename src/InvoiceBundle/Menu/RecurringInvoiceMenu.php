<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidInvoice\CoreBundle\Enum\Menu\MenuPriority;
use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\CoreBundle\Icon;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;

final readonly class RecurringInvoiceMenu
{
    public function __construct(
        private FeatureGate $featureGate,
        private UpgradePromptProvider $upgradePromptProvider,
    ) {
    }

    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_RECURRING_INVOICE->value)]
    public function sidebar(ItemInterface $menu): void
    {
        // Nested under the "Invoices" item (added by InvoiceMenu, which runs
        // first: PRIORITY_INVOICE > PRIORITY_RECURRING_INVOICE) instead of
        // being its own top-level sidebar entry - "Recurring" alone read as
        // unclear out of context, and as a standalone dropdown its longer
        // labels overflowed the fixed-width sidebar column.
        $invoices = $menu->getChild('invoice.menu.main');

        if (! $invoices instanceof ItemInterface) {
            return;
        }

        $extras = [];

        if (! $this->featureGate->isEnabled(Feature::RecurringInvoices->value)) {
            $planLabel = $this->upgradePromptProvider->menuLabel(Feature::RecurringInvoices->value);

            if ($planLabel !== null) {
                $extras['plan_label'] = $planLabel;
            }
        }

        $invoices->addChild(
            'invoice.menu.recurring.list',
            [
                'route' => '_invoices_index_recurring',
                'extras' => $extras + ['icon' => Icon::RECURRING_INVOICE],
            ],
        );
        $invoices->addChild(
            'invoice.menu.recurring.create',
            [
                'route' => '_invoices_create_recurring',
                'extras' => $extras + ['icon' => Icon::RECURRING_INVOICE_ADD],
            ],
        );
    }
}
