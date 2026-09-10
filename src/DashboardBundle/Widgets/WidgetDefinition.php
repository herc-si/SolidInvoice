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

namespace Augias\DashboardBundle\Widgets;

use Augias\DashboardBundle\Enum\WidgetWidth;
use Augias\DashboardBundle\Enum\WidgetZone;

/**
 * A widget plus the metadata the dashboard needs to place it, name it in the
 * picker and store it in a layout.
 *
 * The widget itself is held, not resolved lazily: the container already builds
 * every tagged widget service, and a definition with no widget behind it would
 * only push the null check into every caller.
 */
final readonly class WidgetDefinition
{
    public function __construct(
        public string $id,
        public WidgetInterface $widget,
        public string $label,
        public string $icon,
        public WidgetZone $zone,
        public int $priority = 0,
        public WidgetWidth $width = WidgetWidth::Full,
        public bool $removable = true,
        public string $cssClass = '',
    ) {
    }

    /**
     * The same definition as the user arranged it.
     *
     * Placement is the only thing a layout may override, so it is the only thing
     * this takes: the resolver rebuilds the definition rather than making
     * {@see $zone} or {@see $width} mutable.
     *
     * A null width means the layout has nothing to say about it — a widget the
     * user has never resized, or one saved before widths existed — and the
     * declared default stands.
     */
    public function placedAt(WidgetZone $zone, ?WidgetWidth $width = null): self
    {
        return new self(
            $this->id,
            $this->widget,
            $this->label,
            $this->icon,
            $zone,
            $this->priority,
            $width ?? $this->width,
            $this->removable,
            $this->cssClass,
        );
    }
}
