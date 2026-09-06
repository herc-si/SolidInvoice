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

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->import('@AugiasDashboardBundle/Resources/config/routing.php');

    $routingConfigurator->import('@AugiasSettingsBundle/Resources/config/routing.php')
        ->prefix('/');

    $routingConfigurator->import('@AugiasCoreBundle/Resources/config/routing.php')
        ->prefix('/');

    $routingConfigurator->import('@AugiasInstallBundle/Resources/config/routing.php')
        ->prefix('/');

    $routingConfigurator->import('@AugiasClientBundle/Resources/config/routing.php')
        ->prefix('/clients');

    $routingConfigurator->import('@AugiasQuoteBundle/Resources/config/routing.php')
        ->prefix('/quotes');

    $routingConfigurator->import('@AugiasInvoiceBundle/Resources/config/routing.php')
        ->prefix('/invoices');

    $routingConfigurator->import('@AugiasPaymentBundle/Resources/config/routing.php')
        ->prefix('/payments');

    $routingConfigurator->import('@AugiasTaxBundle/Resources/config/routing.php')
        ->prefix('/tax');

    $routingConfigurator->import('@AugiasUserBundle/Resources/config/routing.php')
        ->prefix('/');

    $routingConfigurator->import('@AugiasNotificationBundle/Resources/config/routing.php')
        ->prefix('/notifications');

    $routingConfigurator->import('@AugiasElectronicInvoicingBundle/Resources/config/routing.php')
        ->prefix('/electronic-invoicing');

    $routingConfigurator->import('@AugiasSupplierBundle/Resources/config/routing.php')
        ->prefix('/suppliers');

    $routingConfigurator->import('@AugiasBillBundle/Resources/config/routing.php')
        ->prefix('/bills');

    $routingConfigurator->import('@AugiasCatalogBundle/Resources/config/routing.php')
        ->prefix('/catalog');

    $routingConfigurator->import('@AugiasMcpBundle/Resources/config/routing.php')
        ->prefix('/');

    $routingConfigurator->import('.', 'mcp');

    $routingConfigurator->import('@AugiasDataGridBundle/Resources/config/routing.php')
        ->prefix('/');
};
