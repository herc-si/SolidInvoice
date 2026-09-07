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

namespace Augias\CoreBundle\Menu;

use Augias\CoreBundle\Enum\Menu\MenuPriority;
use Augias\CoreBundle\Icon;
use Knp\Menu\ItemInterface;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;

/**
 * The "Prestation" section: the three documents that follow a piece of work
 * from estimate to money owed — quote, sales invoice, purchase invoice.
 *
 * The section is created here, in CoreBundle, but its children are added by the
 * bundles that own them ({@see \Augias\QuoteBundle\Menu\QuoteMenu} and friends),
 * each through {@see self::section()}. Keeping the label, icon and identity in
 * one place stops three bundles from each having an opinion about what the
 * section is called, while leaving every entry's route and feature-gating with
 * the bundle that actually knows about it.
 */
final class PrestationMenu
{
    /** Both the item name and its translation key, as everywhere else in the sidebar. */
    public const string SECTION = 'menu.prestation';

    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_PRESTATION->value)]
    public function sidebar(ItemInterface $menu): void
    {
        self::section($menu);
    }

    /**
     * The section, created on first call.
     *
     * Builder priorities are supposed to guarantee this class runs first, so in
     * practice every caller finds the section already there. It creates it
     * anyway if it is missing: a future priority change that got the order
     * wrong should cost a section header, not silently drop the invoices link
     * out of the sidebar entirely.
     */
    public static function section(ItemInterface $menu): ItemInterface
    {
        return $menu->getChild(self::SECTION) ?? $menu->addChild(
            self::SECTION,
            [
                'extras' => [
                    'icon' => Icon::PRESTATION,
                ],
            ],
        );
    }
}
