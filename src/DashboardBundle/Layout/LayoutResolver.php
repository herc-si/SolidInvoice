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

namespace Augias\DashboardBundle\Layout;

use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\WidgetFactory;
use Augias\DashboardBundle\Widgets\WidgetDefinition;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Turns a stored layout into the list of widgets to render.
 *
 * Three things can be wrong with a layout by the time it is read back, and all
 * three are ordinary rather than exceptional:
 *
 *  - it names a widget that no longer exists — dropped;
 *  - it predates a widget that now exists — inserted, near where its declared
 *    priority would have put it, so a new indicator is not born at the bottom
 *    of the page where nobody looks;
 *  - it names a widget that exists but does not currently apply — skipped for
 *    this request only, and left in the layout so it comes back on its own when
 *    it applies again.
 *
 * @see \Augias\DashboardBundle\Tests\Layout\LayoutResolverTest
 */
final readonly class LayoutResolver
{
    public function __construct(
        private WidgetFactory $factory,
        private LoggerInterface $logger,
    ) {
    }

    public function resolve(?DashboardLayout $layout): ResolvedLayout
    {
        $available = $this->available();

        if (! $layout instanceof DashboardLayout || $layout->isEmpty()) {
            return $this->defaults($available);
        }

        $zones = array_fill_keys(array_column(WidgetZone::cases(), 'value'), []);
        $placed = [];

        foreach ($layout->visible as $entry) {
            $definition = $available[$entry['id']] ?? null;

            if (! $definition instanceof WidgetDefinition) {
                continue;
            }

            $placed[$entry['id']] = true;
            $zones[$entry['zone']->value][] = $definition->placedAt($entry['zone'], $entry['width'] ?? null);
        }

        $hidden = [];

        foreach ($layout->hidden as $id) {
            $definition = $available[$id] ?? null;

            // A pinned widget cannot be hidden, whatever the payload said. This
            // is the second line of defence; the save action rejects it too.
            if ($definition instanceof WidgetDefinition && $definition->removable) {
                $placed[$id] = true;
                $hidden[] = $definition;
            }
        }

        foreach ($available as $id => $definition) {
            if (! isset($placed[$id])) {
                $zones[$definition->zone->value] = $this->insertByPriority($zones[$definition->zone->value], $definition);
            }
        }

        return new ResolvedLayout($zones, $hidden);
    }

    /**
     * Widgets that exist and apply, keyed by id, in registration order.
     *
     * @return array<string, WidgetDefinition>
     */
    private function available(): array
    {
        $available = [];

        foreach ($this->factory->all() as $id => $definition) {
            try {
                $supported = $definition->widget->supports();
            } catch (Throwable $e) {
                // supports() usually reads the database, so a failure here is the
                // same outage the widget's own error state exists to report.
                // Treating it as "not supported" would hide the widget and tell
                // the user their dashboard is fine, which is the one answer that
                // is certainly wrong.
                $this->logger->error('Unable to determine whether a dashboard widget applies', [
                    'widget' => $id,
                    'exception' => $e,
                ]);

                $supported = true;
            }

            if ($supported) {
                $available[$id] = $definition;
            }
        }

        return $available;
    }

    /**
     * @param array<string, WidgetDefinition> $available
     */
    private function defaults(array $available): ResolvedLayout
    {
        $zones = array_fill_keys(array_column(WidgetZone::cases(), 'value'), []);

        foreach ($this->factory->defaultLayout() as $definition) {
            if (isset($available[$definition->id])) {
                $zones[$definition->zone->value][] = $definition;
            }
        }

        return new ResolvedLayout($zones);
    }

    /**
     * Slot a newly registered widget in ahead of the first widget it outranks.
     *
     * Once a user has reordered a zone by hand this is only an approximation —
     * their order no longer follows priority, so there is no position that is
     * provably right. Landing near the top for a high priority is closer to the
     * intent than appending, and the user can drag it anyway.
     *
     * @param list<WidgetDefinition> $inZone
     * @return list<WidgetDefinition>
     */
    private function insertByPriority(array $inZone, WidgetDefinition $definition): array
    {
        foreach ($inZone as $index => $existing) {
            if ($definition->priority > $existing->priority) {
                array_splice($inZone, $index, 0, [$definition]);

                return $inZone;
            }
        }

        $inZone[] = $definition;

        return $inZone;
    }
}
