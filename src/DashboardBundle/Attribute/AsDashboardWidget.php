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

namespace Augias\DashboardBundle\Attribute;

use Attribute;
use Augias\DashboardBundle\Enum\WidgetWidth;
use Augias\DashboardBundle\Enum\WidgetZone;

/**
 * Declares a class as a dashboard widget and carries everything about it that
 * is not behaviour.
 *
 * The identity lives here rather than on {@see \Augias\DashboardBundle\Widgets\WidgetInterface}
 * on purpose. A saved layout refers to widgets by {@see $id}, so that string is
 * a storage key with a lifetime longer than any class name: keeping it in an
 * attribute makes it obvious that renaming the class is free and changing the
 * id is not.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsDashboardWidget
{
    /**
     * @param string $id         Stable storage key. Never change it for an existing widget.
     * @param string $label      Translation key naming the widget in the picker.
     * @param string $icon       Tabler icon name, e.g. 'tabler:chart-line'.
     * @param int    $priority   Default ordering within the zone, highest first.
     * @param WidgetWidth $width Default share of the zone. A user can widen or
     *                           narrow it afterwards, and that choice is stored
     *                           per widget, so this is a starting point only.
     * @param bool   $removable  False pins the widget: it renders always and the
     *                           picker offers no way to hide it.
     * @param string $cssClass   Extra classes for the wrapper the renderer emits.
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $icon,
        public WidgetZone $zone = WidgetZone::LeftColumn,
        public int $priority = 0,
        public WidgetWidth $width = WidgetWidth::Full,
        public bool $removable = true,
        public string $cssClass = '',
    ) {
    }
}
