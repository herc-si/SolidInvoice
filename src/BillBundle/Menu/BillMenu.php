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

namespace Augias\BillBundle\Menu;

use Augias\CoreBundle\Enum\Menu\MenuPriority;
use Augias\CoreBundle\Icon;
use Knp\Menu\ItemInterface;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;

final class BillMenu
{
    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_BILL->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $menu->addChild(
            'bill.menu.main',
            [
                'route' => '_bills_index',
                'extras' => [
                    'icon' => Icon::BILL,
                ],
            ],
        );
    }
}
