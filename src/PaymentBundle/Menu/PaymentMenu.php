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

namespace SolidInvoice\PaymentBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidInvoice\CoreBundle\Enum\Menu\MenuPriority;
use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\CoreBundle\Icon;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;

final readonly class PaymentMenu
{
    public function __construct(
        private FeatureGate $featureGate,
        private UpgradePromptProvider $upgradePromptProvider,
    ) {
    }

    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_PAYMENT->value)]
    public function sidebar(ItemInterface $menu): void
    {
        // "Payment methods" is configuration, not a daily task, so it moved to
        // the System section (see CoreBundle\Menu\MainMenu::paymentMethods).
        // That leaves a single destination here, so no dropdown is needed.
        $extras = ['icon' => Icon::PAYMENT];

        if (! $this->featureGate->isEnabled(Feature::OnlinePayments->value)) {
            $planLabel = $this->upgradePromptProvider->menuLabel(Feature::OnlinePayments->value);

            if ($planLabel !== null) {
                $extras['plan_label'] = $planLabel;
            }
        }

        $menu->addChild(
            'payment.menu.main',
            [
                'route' => '_payments_index',
                'extras' => $extras,
            ],
        );
    }
}
