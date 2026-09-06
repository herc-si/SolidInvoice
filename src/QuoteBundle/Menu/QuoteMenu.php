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

namespace Augias\QuoteBundle\Menu;

use Augias\CoreBundle\Enum\Menu\MenuPriority;
use Augias\CoreBundle\Feature\UpgradePromptProvider;
use Augias\CoreBundle\Icon;
use Augias\SaasBundle\Feature\Feature;
use Knp\Menu\ItemInterface;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;

final readonly class QuoteMenu
{
    public function __construct(
        private FeatureGate $featureGate,
        private UpgradePromptProvider $upgradePromptProvider,
    ) {
    }

    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_QUOTE->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $extras = ['icon' => Icon::QUOTE];

        if (! $this->featureGate->isEnabled(Feature::Quotes->value)) {
            $planLabel = $this->upgradePromptProvider->menuLabel(Feature::Quotes->value);

            if ($planLabel !== null) {
                $extras['plan_label'] = $planLabel;
            }
        }

        $menu->addChild(
            'quote.menu.main',
            [
                'route' => '_quotes_index',
                'extras' => $extras,
            ],
        );
    }
}
