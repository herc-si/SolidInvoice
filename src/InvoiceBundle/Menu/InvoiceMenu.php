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

namespace Augias\InvoiceBundle\Menu;

use Augias\CoreBundle\Enum\Menu\MenuPriority;
use Augias\CoreBundle\Icon;
use Knp\Menu\ItemInterface;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;

final class InvoiceMenu
{
    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_INVOICE->value)]
    public function sidebar(ItemInterface $menu): void
    {
        // Flat link rather than a dropdown: the list page already carries a
        // primary "create" button, so a submenu only added a click to reach
        // the list, which is the far more frequent destination.
        $menu->addChild(
            'invoice.menu.main',
            [
                'route' => '_invoices_index',
                'extras' => [
                    'icon' => Icon::INVOICE,
                ],
            ],
        );
    }
}
