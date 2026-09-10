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

use Augias\DashboardBundle\Enum\WidgetWidth;
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
     * Bumped only for a change the reader cannot absorb. The per-widget width
     * added here is not one: a payload written before widths existed simply
     * lacks the key, and a missing key already means "whatever the widget
     * declares".
     */
    public const int VERSION = 1;

    /**
     * The width key is optional. Both an absent key and a null value mean "the
     * widget's declared default stands", and {@see fromArray()} only ever
     * produces the absent form, so a document that came from one has a single
     * spelling for it. Null is tolerated for the benefit of layouts built by
     * hand — a test, a fixture, a future default arrangement.
     *
     * @param list<array{id: string, zone: WidgetZone, width?: ?WidgetWidth}> $visible ordered, first rendered first
     * @param list<string>                                                    $hidden  ids the user removed
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
            $placement = ['id' => $entry['id'], 'zone' => $zone];
            $width = WidgetWidth::tryFromName(is_string($entry['width'] ?? null) ? $entry['width'] : null);

            // An unreadable width is dropped rather than recorded as null, so
            // that "the widget knows best" has exactly one spelling: the absence
            // of the key. A payload written before widths existed and one whose
            // width came back as nonsense then produce the same entry, which is
            // what makes toArray() the inverse of this.
            if ($width instanceof WidgetWidth) {
                $placement['width'] = $width;
            }

            $visible[] = $placement;
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
     * @return array{v: int, widgets: list<array{id: string, zone: string, width?: string}>, hidden: list<string>}
     */
    public function toArray(): array
    {
        return [
            'v' => self::VERSION,
            'widgets' => array_map(
                static function (array $entry): array {
                    $widget = ['id' => $entry['id'], 'zone' => $entry['zone']->value];

                    // Omitted rather than written as null: a widget the user has
                    // never resized should keep following the declared default,
                    // including after that default changes.
                    if (($entry['width'] ?? null) instanceof WidgetWidth) {
                        $widget['width'] = $entry['width']->value;
                    }

                    return $widget;
                },
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
