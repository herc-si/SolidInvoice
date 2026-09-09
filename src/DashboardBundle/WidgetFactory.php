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

namespace Augias\DashboardBundle;

use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Widgets\WidgetDefinition;
use Augias\DashboardBundle\Widgets\WidgetInterface;

/**
 * Every widget the application knows how to render, keyed by its stable id.
 *
 * This used to hand out one SplPriorityQueue per zone, which was the whole
 * design: the compiled priorities *were* the layout, and Twig pulled a zone and
 * rendered whatever came out. That cannot survive per-user layouts, because the
 * order now comes from the database. So the registry answers "what exists" and
 * {@see \Augias\DashboardBundle\Layout\LayoutResolver} answers "what goes where"
 * — the priorities below are only the default the resolver starts from.
 *
 * @see \Augias\DashboardBundle\Tests\WidgetFactoryTest
 */
final class WidgetFactory
{
    /**
     * @var array<string, WidgetDefinition>
     */
    private array $widgets = [];

    public function add(
        WidgetInterface $widget,
        string $id,
        string $label,
        string $icon,
        string $zone,
        int $priority = 0,
        bool $removable = true,
        string $cssClass = '',
    ): void {
        $this->widgets[$id] = new WidgetDefinition(
            $id,
            $widget,
            $label,
            $icon,
            WidgetZone::from($zone),
            $priority,
            $removable,
            $cssClass,
        );
    }

    public function has(string $id): bool
    {
        return isset($this->widgets[$id]);
    }

    public function get(string $id): ?WidgetDefinition
    {
        return $this->widgets[$id] ?? null;
    }

    /**
     * @return array<string, WidgetDefinition>
     */
    public function all(): array
    {
        return $this->widgets;
    }

    /**
     * The layout a user gets before they have ever touched anything: every
     * widget in its declared zone, ordered by priority, highest first.
     *
     * Ties keep registration order. The container registers services in a stable
     * order, so two widgets sharing a priority do not swap places between
     * requests — but a widget that cares about its neighbour should say so with
     * a priority rather than rely on that.
     *
     * @return list<WidgetDefinition>
     */
    public function defaultLayout(): array
    {
        $ordered = [];

        foreach (WidgetZone::cases() as $zone) {
            $inZone = array_values(array_filter(
                $this->widgets,
                static fn (WidgetDefinition $definition): bool => $definition->zone === $zone,
            ));

            usort(
                $inZone,
                static fn (WidgetDefinition $a, WidgetDefinition $b): int => $b->priority <=> $a->priority,
            );

            $ordered = [...$ordered, ...$inZone];
        }

        return $ordered;
    }
}
