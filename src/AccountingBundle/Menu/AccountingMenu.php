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

namespace Augias\AccountingBundle\Menu;

use Augias\CoreBundle\Enum\Menu\MenuPriority;
use Augias\CoreBundle\Icon;
use Knp\Menu\ItemInterface;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;

final class AccountingMenu
{
    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_ACCOUNTING->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $menu->addChild(
            'accounting.menu.main',
            [
                'route' => '_accounting_index',
                'extras' => [
                    'icon' => Icon::ACCOUNTING,
                ],
            ],
        );
    }
}
