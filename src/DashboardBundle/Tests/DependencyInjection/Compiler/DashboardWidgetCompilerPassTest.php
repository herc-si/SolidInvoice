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

namespace Augias\DashboardBundle\Tests\DependencyInjection\Compiler;

use Augias\DashboardBundle\DependencyInjection\Compiler\DashboardWidgetCompilerPass;
use Augias\DashboardBundle\Tests\Fixtures\StubWidget;
use Augias\DashboardBundle\WidgetFactory;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class DashboardWidgetCompilerPassTest extends TestCase
{
    public function testFeedsEachTaggedWidgetIntoTheRegistry(): void
    {
        $container = $this->container();
        $container->setDefinition('widget.revenue', $this->widget([
            'id' => 'revenue_chart',
            'label' => 'label.revenue',
            'icon' => 'tabler:chart-line',
            'zone' => 'left_column',
            'priority' => 100,
        ]));

        (new DashboardWidgetCompilerPass())->process($container);

        $calls = $container->getDefinition(WidgetFactory::class)->getMethodCalls();

        self::assertCount(1, $calls);
        self::assertSame('add', $calls[0][0]);
        self::assertSame(
            ['revenue_chart', 'label.revenue', 'tabler:chart-line', 'left_column', 100, 'full', true, ''],
            array_slice($calls[0][1], 1),
        );
    }

    public function testDoesNothingWithoutARegistry(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('widget.revenue', $this->widget(['id' => 'a', 'label' => 'l', 'icon' => 'i', 'zone' => 'top']));

        (new DashboardWidgetCompilerPass())->process($container);

        self::assertFalse($container->hasDefinition(WidgetFactory::class));
    }

    /**
     * Two widgets claiming one id would silently overwrite each other, and every
     * saved layout naming that id would point at whichever won the race. That is
     * a bug in the code, so it fails the build rather than the request.
     */
    public function testRejectsADuplicateWidgetId(): void
    {
        $container = $this->container();
        $tag = ['id' => 'same', 'label' => 'l', 'icon' => 'i', 'zone' => 'top'];
        $container->setDefinition('widget.one', $this->widget($tag));
        $container->setDefinition('widget.two', $this->widget($tag));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Duplicate dashboard widget id "same"');

        (new DashboardWidgetCompilerPass())->process($container);
    }

    /**
     * @param array<string, string> $tag
     */
    #[DataProvider('incompleteTags')]
    public function testRejectsATagMissingSomethingTheRegistryNeeds(array $tag, string $missing): void
    {
        $container = $this->container();
        $container->setDefinition('widget.incomplete', $this->widget($tag));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('is missing the required "%s" attribute', $missing));

        (new DashboardWidgetCompilerPass())->process($container);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function incompleteTags(): iterable
    {
        yield 'no id' => [['label' => 'l', 'icon' => 'i', 'zone' => 'top'], 'id'];
        yield 'no label' => [['id' => 'a', 'icon' => 'i', 'zone' => 'top'], 'label'];
        yield 'no icon' => [['id' => 'a', 'label' => 'l', 'zone' => 'top'], 'icon'];
        yield 'no zone' => [['id' => 'a', 'label' => 'l', 'icon' => 'i'], 'zone'];
        yield 'blank id' => [['id' => '', 'label' => 'l', 'icon' => 'i', 'zone' => 'top'], 'id'];
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(WidgetFactory::class, new Definition(WidgetFactory::class));

        return $container;
    }

    /**
     * @param array<string, scalar> $tag
     */
    private function widget(array $tag): Definition
    {
        return (new Definition(StubWidget::class))->addTag('dashboard.widget', $tag);
    }
}
