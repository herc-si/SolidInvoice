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
        public bool $removable = true,
        public string $cssClass = '',
    ) {
    }

    /**
     * The same definition placed in a different zone.
     *
     * A user moving a card between columns changes only where it sits, so the
     * resolver rebuilds the definition rather than making {@see $zone} mutable.
     */
    public function inZone(WidgetZone $zone): self
    {
        return new self(
            $this->id,
            $this->widget,
            $this->label,
            $this->icon,
            $zone,
            $this->priority,
            $this->removable,
            $this->cssClass,
        );
    }
}
