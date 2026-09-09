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
use Augias\DashboardBundle\Layout\LayoutResolver;
use Augias\DashboardBundle\Tests\Fixtures\StubWidget;
use Augias\DashboardBundle\WidgetFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Reconciliation is where this feature either survives its own upgrades or does
 * not: the stored layout and the shipped widget set drift apart on every
 * release, and every test here is one of the ways they drift.
 */
final class LayoutResolverTest extends TestCase
{
    public function testFallsBackToTheDefaultLayoutWhenNothingIsStored(): void
    {
        $resolved = $this->resolver($this->factory())->resolve(null);

        self::assertSame(['pinned'], $this->ids($resolved->zone(WidgetZone::Top)));
        self::assertSame(['attention', 'revenue'], $this->ids($resolved->zone(WidgetZone::LeftColumn)));
        self::assertSame(['activity'], $this->ids($resolved->zone(WidgetZone::RightColumn)));
        self::assertSame([], $resolved->hidden);
    }

    public function testAnEmptyStoredLayoutIsTreatedAsNeverCustomised(): void
    {
        $resolved = $this->resolver($this->factory())->resolve(new DashboardLayout());

        self::assertSame(['attention', 'revenue'], $this->ids($resolved->zone(WidgetZone::LeftColumn)));
    }

    public function testStoredOrderAndZoneWin(): void
    {
        $layout = new DashboardLayout([
            ['id' => 'pinned', 'zone' => WidgetZone::Top],
            ['id' => 'revenue', 'zone' => WidgetZone::RightColumn],
            ['id' => 'attention', 'zone' => WidgetZone::RightColumn],
            ['id' => 'activity', 'zone' => WidgetZone::LeftColumn],
        ]);

        $resolved = $this->resolver($this->factory())->resolve($layout);

        self::assertSame(['activity'], $this->ids($resolved->zone(WidgetZone::LeftColumn)));
        self::assertSame(['revenue', 'attention'], $this->ids($resolved->zone(WidgetZone::RightColumn)));

        // The definition follows the widget into its new column, so the renderer
        // and the payload agree about where it is.
        self::assertSame(WidgetZone::RightColumn, $resolved->zone(WidgetZone::RightColumn)[0]->zone);
    }

    public function testHiddenWidgetsAreOfferedBackInsteadOfRendered(): void
    {
        $layout = new DashboardLayout(
            [['id' => 'attention', 'zone' => WidgetZone::LeftColumn]],
            ['revenue', 'activity', 'pinned'],
        );

        $resolved = $this->resolver($this->factory())->resolve($layout);

        self::assertSame(['attention'], $this->ids($resolved->zone(WidgetZone::LeftColumn)));
        self::assertSame(['revenue', 'activity'], $this->ids($resolved->hidden));

        // 'pinned' is not removable, so hiding it is refused and it renders anyway.
        self::assertSame(['pinned'], $this->ids($resolved->zone(WidgetZone::Top)));
    }

    /**
     * The upgrade case. A layout saved before a widget existed must not bury it:
     * a new indicator nobody ever sees is the same as not shipping it.
     */
    public function testAWidgetAddedSinceTheLayoutWasSavedIsInsertedByPriority(): void
    {
        $factory = $this->factory();
        $factory->add(new StubWidget(), 'threshold', 'l', 'i', 'left_column', 500);

        $layout = new DashboardLayout([
            ['id' => 'attention', 'zone' => WidgetZone::LeftColumn],
            ['id' => 'revenue', 'zone' => WidgetZone::LeftColumn],
        ]);

        $resolved = $this->resolver($factory)->resolve($layout);

        self::assertSame(['threshold', 'attention', 'revenue'], $this->ids($resolved->zone(WidgetZone::LeftColumn)));
    }

    public function testALowPriorityNewWidgetLandsAtTheBottomOfItsZone(): void
    {
        $factory = $this->factory();
        $factory->add(new StubWidget(), 'footnote', 'l', 'i', 'left_column', -50);

        $layout = new DashboardLayout([
            ['id' => 'attention', 'zone' => WidgetZone::LeftColumn],
            ['id' => 'revenue', 'zone' => WidgetZone::LeftColumn],
        ]);

        $resolved = $this->resolver($factory)->resolve($layout);

        self::assertSame(['attention', 'revenue', 'footnote'], $this->ids($resolved->zone(WidgetZone::LeftColumn)));
    }

    public function testAWidgetRemovedFromTheApplicationIsDroppedFromAStoredLayout(): void
    {
        $layout = new DashboardLayout(
            [
                ['id' => 'attention', 'zone' => WidgetZone::LeftColumn],
                ['id' => 'deleted_in_v3', 'zone' => WidgetZone::LeftColumn],
            ],
            ['also_gone'],
        );

        $resolved = $this->resolver($this->factory())->resolve($layout);

        self::assertSame(['attention', 'revenue'], $this->ids($resolved->zone(WidgetZone::LeftColumn)));
        self::assertSame([], $this->ids($resolved->hidden));
    }

    /**
     * Unsupported is not the same as hidden: the widget stays in the layout and
     * comes back on its own once it applies again, so switching a feature off and
     * on does not cost the user their arrangement.
     */
    public function testAnUnsupportedWidgetIsSkippedWithoutBeingOfferedInThePicker(): void
    {
        $factory = $this->factory();
        $factory->add(new StubWidget(supported: false), 'accounting', 'l', 'i', 'left_column', 400);

        $resolved = $this->resolver($factory)->resolve(new DashboardLayout([
            ['id' => 'pinned', 'zone' => WidgetZone::Top],
            ['id' => 'accounting', 'zone' => WidgetZone::LeftColumn],
            ['id' => 'attention', 'zone' => WidgetZone::LeftColumn],
            ['id' => 'revenue', 'zone' => WidgetZone::LeftColumn],
            ['id' => 'activity', 'zone' => WidgetZone::RightColumn],
        ]));

        self::assertSame(['attention', 'revenue'], $this->ids($resolved->zone(WidgetZone::LeftColumn)));
        self::assertSame([], $this->ids($resolved->hidden));
    }

    /**
     * A supports() that cannot reach the database is an outage, and an outage
     * must not read as "this widget does not apply to you" — the widget renders
     * so it can show its own error state.
     */
    public function testAFailingSupportsCheckStillRendersTheWidget(): void
    {
        $factory = new WidgetFactory();
        $factory->add(new StubWidget(supportsFailure: new RuntimeException('database is down')), 'flaky', 'l', 'i', 'top', 10);

        $resolved = $this->resolver($factory)->resolve(null);

        self::assertSame(['flaky'], $this->ids($resolved->zone(WidgetZone::Top)));
    }

    private function factory(): WidgetFactory
    {
        $factory = new WidgetFactory();
        $factory->add(new StubWidget(), 'pinned', 'l', 'i', 'top', 300, false);
        $factory->add(new StubWidget(), 'attention', 'l', 'i', 'left_column', 120);
        $factory->add(new StubWidget(), 'revenue', 'l', 'i', 'left_column', 100);
        $factory->add(new StubWidget(), 'activity', 'l', 'i', 'right_column', 50);

        return $factory;
    }

    private function resolver(WidgetFactory $factory): LayoutResolver
    {
        return new LayoutResolver($factory, new NullLogger());
    }

    /**
     * @param list<\Augias\DashboardBundle\Widgets\WidgetDefinition> $definitions
     * @return list<string>
     */
    private function ids(array $definitions): array
    {
        return array_column($definitions, 'id');
    }
}
