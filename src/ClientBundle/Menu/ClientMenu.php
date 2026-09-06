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

namespace SolidInvoice\ClientBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidInvoice\CoreBundle\Enum\Menu\MenuPriority;
use SolidInvoice\CoreBundle\Icon;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;
use SolidWorx\Platform\PlatformBundle\Menu\Options;

final class ClientMenu
{
    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_CLIENT->value)]
    public function sidebar(ItemInterface $menu): void
    {
        // Single click, straight to the list — a "Tiers" record covers both
        // clients and suppliers (see Client::$isClient / $isSupplier), so
        // this is the only entry point needed; SupplierBundle no longer adds
        // its own sidebar item.
        $menu->addChild(
            'client.menu.main',
            Options::create()
                ->icon(Icon::CLIENT)
                ->route('_clients_index')
                ->build(),
        );
    }
}
