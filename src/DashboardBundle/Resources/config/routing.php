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

use Augias\DashboardBundle\Action\DismissOnboardingChecklist;
use Augias\DashboardBundle\Action\Index;
use Augias\DashboardBundle\Action\ResetDashboardLayout;
use Augias\DashboardBundle\Action\SaveDashboardLayout;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_dashboard', '/dashboard')
        ->controller(Index::class);
    $routingConfigurator
        ->add('_dashboard_onboarding_dismiss', '/dashboard/onboarding/dismiss')
        ->controller(DismissOnboardingChecklist::class)
        ->methods(['POST'])
    ;
    $routingConfigurator
        ->add('_dashboard_layout_save', '/dashboard/layout')
        ->controller(SaveDashboardLayout::class)
        ->methods(['POST'])
    ;
    $routingConfigurator
        ->add('_dashboard_layout_reset', '/dashboard/layout/reset')
        ->controller(ResetDashboardLayout::class)
        ->methods(['POST'])
    ;
};
