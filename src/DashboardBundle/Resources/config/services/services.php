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

use Augias\DashboardBundle\AugiasDashboardBundle;
use Augias\DashboardBundle\Checklist\ChecklistItemInterface;
use Augias\DashboardBundle\Checklist\ChecklistManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
    ;

    // Auto-tag checklist items (must come before load())
    $services
        ->instanceof(ChecklistItemInterface::class)
        ->tag('dashboard.checklist_item');

    $services
        ->load(AugiasDashboardBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{Attribute,DependencyInjection,Entity,Enum,Layout/DashboardLayout.php,Layout/ResolvedLayout.php,Resources,Tests,Widgets/WidgetDefinition.php}');

    $services
        ->load(AugiasDashboardBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    // Configure ChecklistManager with tagged items
    $services
        ->set(ChecklistManager::class)
        ->arg('$items', tagged_iterator('dashboard.checklist_item'));

    // Widget placement is no longer configured here. Each widget carries an
    // #[AsDashboardWidget] attribute naming its id, label, icon, default zone
    // and default priority, and DashboardWidgetCompilerPass feeds those into the
    // registry. The zone and priority there are only the *default* arrangement:
    // what a user actually sees comes from their stored layout, reconciled by
    // LayoutResolver. Moving a widget by editing this file would have had no
    // effect for anyone who had ever dragged a card.
};
