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

/**
 * A user's stored dashboard arrangement, exactly as it round-trips through
 * `user_settings.setting_value`.
 *
 * Visible and hidden are both recorded, and that redundancy is the point. If
 * only the visible list were stored, a widget missing from it would be
 * ambiguous: hidden on purpose, or added to the application after this layout
 * was saved? The first must stay gone, the second must appear. Listing hidden
 * ids explicitly is what lets {@see LayoutResolver} tell them apart.
 *
 * @see \Augias\DashboardBundle\Tests\Layout\DashboardLayoutTest
 */
final readonly class DashboardLayout
{
    /**
     * Bumped only for a change the reader cannot absorb. Adding an optional key
     * (a per-widget width, say) is not one: an old payload simply lacks it.
     */
    public const int VERSION = 1;

    /**
     * @param list<array{id: string, zone: WidgetZone}> $visible ordered, first rendered first
     * @param list<string>                              $hidden  ids the user removed
     */
    public function __construct(
        public array $visible = [],
        public array $hidden = [],
    ) {
    }

    /**
     * Rebuild from decoded JSON, discarding anything malformed.
     *
     * Every field is treated as hostile. This payload was written by a browser
     * request, and a layout that throws on a stray value would lock the user out
     * of their own dashboard with no way back except a database edit. Garbage in
     * means a shorter layout, never an exception.
     *
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $visible = [];
        $seen = [];

        foreach (is_array($data['widgets'] ?? null) ? $data['widgets'] : [] as $entry) {
            if (! is_array($entry) || ! is_string($entry['id'] ?? null)) {
                continue;
            }

            $zone = WidgetZone::tryFromName(is_string($entry['zone'] ?? null) ? $entry['zone'] : null);

            // A widget listed twice would render twice and be dragged as two
            // cards that write back one id, so the first placement wins.
            if (! $zone instanceof WidgetZone || isset($seen[$entry['id']])) {
                continue;
            }

            $seen[$entry['id']] = true;
            $visible[] = ['id' => $entry['id'], 'zone' => $zone];
        }

        $hidden = [];

        foreach (is_array($data['hidden'] ?? null) ? $data['hidden'] : [] as $id) {
            // Visible wins over hidden: a payload claiming both is contradictory,
            // and showing a widget is the recoverable half of the mistake.
            if (is_string($id) && ! isset($seen[$id])) {
                $hidden[$id] = true;
            }
        }

        return new self($visible, array_keys($hidden));
    }

    /**
     * @return array{v: int, widgets: list<array{id: string, zone: string}>, hidden: list<string>}
     */
    public function toArray(): array
    {
        return [
            'v' => self::VERSION,
            'widgets' => array_map(
                static fn (array $entry): array => ['id' => $entry['id'], 'zone' => $entry['zone']->value],
                $this->visible,
            ),
            'hidden' => $this->hidden,
        ];
    }

    public function isEmpty(): bool
    {
        return [] === $this->visible && [] === $this->hidden;
    }
}
