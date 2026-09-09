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

namespace Augias\DashboardBundle\Tests;

use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Tests\Fixtures\StubWidget;
use Augias\DashboardBundle\WidgetFactory;
use PHPUnit\Framework\TestCase;

final class WidgetFactoryTest extends TestCase
{
    public function testAddAndGet(): void
    {
        $factory = new WidgetFactory();
        $widget = new StubWidget();

        $factory->add($widget, 'revenue_chart', 'label.revenue', 'tabler:chart-line', 'left_column', 100);

        $definition = $factory->get('revenue_chart');

        self::assertNotNull($definition);
        self::assertSame('revenue_chart', $definition->id);
        self::assertSame($widget, $definition->widget);
        self::assertSame('label.revenue', $definition->label);
        self::assertSame('tabler:chart-line', $definition->icon);
        self::assertSame(WidgetZone::LeftColumn, $definition->zone);
        self::assertSame(100, $definition->priority);
        self::assertTrue($definition->removable);
    }

    public function testGetReturnsNullForAnUnknownId(): void
    {
        $factory = new WidgetFactory();

        self::assertFalse($factory->has('nope'));
        self::assertNull($factory->get('nope'));
    }

    public function testDefaultLayoutOrdersByZoneThenPriority(): void
    {
        $factory = new WidgetFactory();

        $factory->add(new StubWidget(), 'low_left', 'l', 'i', 'left_column', 10);
        $factory->add(new StubWidget(), 'right', 'l', 'i', 'right_column', 500);
        $factory->add(new StubWidget(), 'high_left', 'l', 'i', 'left_column', 900);
        $factory->add(new StubWidget(), 'top', 'l', 'i', 'top', 1);

        self::assertSame(
            ['top', 'high_left', 'low_left', 'right'],
            array_column($factory->defaultLayout(), 'id'),
        );
    }

    /**
     * Zones come first whatever the priorities say: a top-zone widget with a
     * priority of 1 still outranks a right-column widget with 500, because they
     * are not competing for the same space.
     */
    public function testDefaultLayoutNeverMixesZones(): void
    {
        $factory = new WidgetFactory();

        $factory->add(new StubWidget(), 'right', 'l', 'i', 'right_column', 999);
        $factory->add(new StubWidget(), 'top', 'l', 'i', 'top', 0);

        $zones = array_map(
            static fn (object $definition): string => $definition->zone->value,
            $factory->defaultLayout(),
        );

        self::assertSame(['top', 'right_column'], $zones);
    }

    public function testAllIsKeyedById(): void
    {
        $factory = new WidgetFactory();
        $factory->add(new StubWidget(), 'a', 'l', 'i', 'top');
        $factory->add(new StubWidget(), 'b', 'l', 'i', 'top');

        self::assertSame(['a', 'b'], array_keys($factory->all()));
    }
}
