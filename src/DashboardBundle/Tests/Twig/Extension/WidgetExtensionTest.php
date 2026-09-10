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

namespace Augias\DashboardBundle\Tests\Twig\Extension;

use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Layout\LayoutProviderInterface;
use Augias\DashboardBundle\Layout\ResolvedLayout;
use Augias\DashboardBundle\Tests\Fixtures\StubWidget;
use Augias\DashboardBundle\Twig\Extension\WidgetExtension;
use Augias\DashboardBundle\Widgets\WidgetDefinition;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

final class WidgetExtensionTest extends TestCase
{
    private const array TEMPLATES = [
        'stub.html.twig' => '{{ a }}{{ b }}',
        'blank.html.twig' => '   ',
        'boom.html.twig' => '{{ missing.property }}',
        '@AugiasDashboard/Widget/_wrapper.html.twig' => '<div id="{{ widget.id }}" data-zone="{{ zone }}">{{ content|raw }}</div>',
        '@AugiasDashboard/Widget/_widget_error.html.twig' => '<p>broken</p>',
    ];

    public function testGetFunctions(): void
    {
        $extension = $this->extension(new ResolvedLayout([]));
        $functions = $extension->getFunctions();

        self::assertCount(3, $functions);
        self::assertContainsOnlyInstancesOf(TwigFunction::class, $functions);
        self::assertSame(
            ['render_dashboard_zone', 'dashboard_hidden_widgets', 'dashboard_widget_widths'],
            array_map(static fn (TwigFunction $function): string => $function->getName(), $functions),
        );
    }

    public function testRendersEachWidgetOfAZoneInOrderInsideItsWrapper(): void
    {
        $layout = new ResolvedLayout([
            'left_column' => [
                $this->definition('first', new StubWidget('stub.html.twig', ['a' => '1', 'b' => '2'])),
                $this->definition('second', new StubWidget('stub.html.twig', ['a' => '3', 'b' => '4'])),
            ],
        ]);

        $content = $this->extension($layout)->renderDashboardZone($this->twig(), 'left_column');

        self::assertSame(
            '<div id="first" data-zone="left_column">12</div><div id="second" data-zone="left_column">34</div>',
            $content,
        );
    }

    public function testAnEmptyZoneRendersNothing(): void
    {
        self::assertSame('', $this->extension(new ResolvedLayout([]))->renderDashboardZone($this->twig(), 'top'));
    }

    public function testAnUnknownZoneRendersNothingRatherThanThrowing(): void
    {
        self::assertSame('', $this->extension(new ResolvedLayout([]))->renderDashboardZone($this->twig(), 'basement'));
    }

    /**
     * The point of the whole wrapper: one widget failing used to take the page
     * with it. The neighbours must still render, and the failure must look like
     * a failure rather than like an empty card.
     */
    public function testAFailingWidgetIsContainedToItsOwnCard(): void
    {
        $layout = new ResolvedLayout([
            'top' => [
                $this->definition('broken', new StubWidget('boom.html.twig')),
                $this->definition('fine', new StubWidget('stub.html.twig', ['a' => 'ok', 'b' => '!'])),
            ],
        ]);

        $content = $this->extension($layout)->renderDashboardZone($this->twig(true), 'top');

        self::assertSame(
            '<div id="broken" data-zone="top"><p>broken</p></div><div id="fine" data-zone="top">ok!</div>',
            $content,
        );
    }

    public function testAWidgetThatRendersNothingGetsNoWrapper(): void
    {
        $layout = new ResolvedLayout([
            'top' => [$this->definition('silent', new StubWidget('blank.html.twig'))],
        ]);

        self::assertSame('', $this->extension($layout)->renderDashboardZone($this->twig(), 'top'));
    }

    public function testHiddenWidgetsAreExposedForThePicker(): void
    {
        $hidden = $this->definition('put_away', new StubWidget());
        $extension = $this->extension(new ResolvedLayout([], [$hidden]));

        self::assertSame([$hidden], $extension->hiddenWidgets());
    }

    private function extension(ResolvedLayout $layout): WidgetExtension
    {
        $provider = new class($layout) implements LayoutProviderInterface {
            public function __construct(
                private readonly ResolvedLayout $layout
            ) {
            }

            public function currentLayout(): ResolvedLayout
            {
                return $this->layout;
            }
        };

        return new WidgetExtension($provider, new NullLogger());
    }

    private function definition(string $id, StubWidget $widget): WidgetDefinition
    {
        return new WidgetDefinition($id, $widget, 'label.' . $id, 'tabler:box', WidgetZone::Top);
    }

    private function twig(bool $strict = false): Environment
    {
        return new Environment(new ArrayLoader(self::TEMPLATES), ['strict_variables' => $strict]);
    }
}
