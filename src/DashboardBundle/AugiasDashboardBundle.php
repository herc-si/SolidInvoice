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

use Augias\DashboardBundle\Attribute\AsDashboardWidget;
use Augias\DashboardBundle\DependencyInjection\Compiler\DashboardWidgetCompilerPass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AugiasDashboardBundle extends Bundle
{
    public const NAMESPACE = __NAMESPACE__;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DashboardWidgetCompilerPass());

        $container->registerAttributeForAutoconfiguration(
            AsDashboardWidget::class,
            static function (ChildDefinition $definition, AsDashboardWidget $attribute): void {
                $definition->addTag('dashboard.widget', [
                    'id' => $attribute->id,
                    'label' => $attribute->label,
                    'icon' => $attribute->icon,
                    // Tag attributes are written into the compiled container, so
                    // the enum is flattened here rather than shipped as an object.
                    'zone' => $attribute->zone->value,
                    'priority' => $attribute->priority,
                    'removable' => $attribute->removable,
                    'cssClass' => $attribute->cssClass,
                ]);
            },
        );
    }
}
