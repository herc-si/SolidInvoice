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

namespace Augias\DashboardBundle\Tests\Layout;

use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Layout\DashboardLayout;
use PHPUnit\Framework\TestCase;

/**
 * fromArray() reads a document a browser wrote, so most of these cases are about
 * what it refuses rather than what it accepts. The rule throughout: drop the bad
 * entry, keep the layout — never throw, because an exception here would lock the
 * user out of their own dashboard with no way back.
 */
final class DashboardLayoutTest extends TestCase
{
    public function testRoundTripsThroughAnArray(): void
    {
        $layout = new DashboardLayout(
            [['id' => 'hero_stats', 'zone' => WidgetZone::Top]],
            ['revenue_chart'],
        );

        self::assertSame([
            'v' => 1,
            'widgets' => [['id' => 'hero_stats', 'zone' => 'top']],
            'hidden' => ['revenue_chart'],
        ], $layout->toArray());

        self::assertEquals($layout, DashboardLayout::fromArray($layout->toArray()));
    }

    public function testEmptyPayloadIsAnEmptyLayout(): void
    {
        self::assertTrue(DashboardLayout::fromArray([])->isEmpty());
    }

    public function testDropsEntriesThatAreNotShapedLikeWidgets(): void
    {
        $layout = DashboardLayout::fromArray([
            'widgets' => [
                'not-an-array',
                ['zone' => 'top'],
                ['id' => 42, 'zone' => 'top'],
                ['id' => 'good', 'zone' => 'top'],
            ],
        ]);

        self::assertSame(['good'], array_column($layout->visible, 'id'));
    }

    public function testDropsAnUnknownZone(): void
    {
        $layout = DashboardLayout::fromArray([
            'widgets' => [
                ['id' => 'nowhere', 'zone' => 'basement'],
                ['id' => 'somewhere', 'zone' => 'right_column'],
            ],
        ]);

        self::assertSame(['somewhere'], array_column($layout->visible, 'id'));
        self::assertSame(WidgetZone::RightColumn, $layout->visible[0]['zone']);
    }

    /**
     * A duplicated id would render the same widget twice, and both copies would
     * write back the same id — so the second drag would silently undo the first.
     */
    public function testKeepsOnlyTheFirstPlacementOfADuplicatedId(): void
    {
        $layout = DashboardLayout::fromArray([
            'widgets' => [
                ['id' => 'twice', 'zone' => 'top'],
                ['id' => 'twice', 'zone' => 'right_column'],
            ],
        ]);

        self::assertCount(1, $layout->visible);
        self::assertSame(WidgetZone::Top, $layout->visible[0]['zone']);
    }

    /**
     * Being told a widget is both shown and hidden is a contradiction; showing it
     * is the half the user can undo.
     */
    public function testVisibleWinsOverHidden(): void
    {
        $layout = DashboardLayout::fromArray([
            'widgets' => [['id' => 'both', 'zone' => 'top']],
            'hidden' => ['both', 'genuinely_hidden'],
        ]);

        self::assertSame(['both'], array_column($layout->visible, 'id'));
        self::assertSame(['genuinely_hidden'], $layout->hidden);
    }

    public function testIgnoresNonStringHiddenIdsAndScalarLists(): void
    {
        $layout = DashboardLayout::fromArray([
            'widgets' => 'nope',
            'hidden' => ['fine', 17, ['nested']],
        ]);

        self::assertSame([], $layout->visible);
        self::assertSame(['fine'], $layout->hidden);
    }
}
