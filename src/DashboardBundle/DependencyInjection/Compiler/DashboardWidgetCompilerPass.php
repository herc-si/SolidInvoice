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

namespace Augias\DashboardBundle\DependencyInjection\Compiler;

use Augias\DashboardBundle\WidgetFactory;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Feeds every `dashboard.widget` service into the registry.
 *
 * The tag is normally written by the #[AsDashboardWidget] attribute rather than
 * by hand, but a hand-written tag is still honoured — it carries the same keys.
 *
 * @see \Augias\DashboardBundle\Tests\DependencyInjection\Compiler\DashboardWidgetCompilerPassTest
 */
final class DashboardWidgetCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(WidgetFactory::class)) {
            return;
        }

        $definition = $container->getDefinition(WidgetFactory::class);
        $seen = [];

        foreach ($container->findTaggedServiceIds('dashboard.widget') as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                foreach (['id', 'label', 'icon', 'zone'] as $required) {
                    if (! isset($attributes[$required]) || '' === $attributes[$required]) {
                        throw new LogicException(sprintf('The "dashboard.widget" tag on service "%s" is missing the required "%s" attribute.', $serviceId, $required));
                    }
                }

                $id = (string) $attributes['id'];

                // Two widgets sharing an id would silently overwrite each other in
                // the registry, and every saved layout naming that id would then
                // point at whichever one happened to be registered last. Fail at
                // compile time instead: this is a bug, not a configuration choice.
                if (isset($seen[$id])) {
                    throw new LogicException(sprintf('Duplicate dashboard widget id "%s": declared by both "%s" and "%s".', $id, $seen[$id], $serviceId));
                }

                $seen[$id] = $serviceId;

                $definition->addMethodCall('add', [
                    new Reference($serviceId),
                    $id,
                    (string) $attributes['label'],
                    (string) $attributes['icon'],
                    (string) $attributes['zone'],
                    (int) ($attributes['priority'] ?? 0),
                    (bool) ($attributes['removable'] ?? true),
                    (string) ($attributes['cssClass'] ?? ''),
                ]);
            }
        }
    }
}
