<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Augias\CatalogBundle\Action\Add;
use Augias\CatalogBundle\Action\Edit;
use Augias\CatalogBundle\Action\Index;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_catalog_index', '/')
        ->controller(Index::class);

    $routingConfigurator
        ->add('_catalog_add', '/add')
        ->controller(Add::class);

    $routingConfigurator
        ->add('_catalog_edit', '/edit/{id}')
        ->controller(Edit::class);
};
