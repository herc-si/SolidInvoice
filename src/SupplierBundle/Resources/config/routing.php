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

use Augias\SupplierBundle\Action\Add;
use Augias\SupplierBundle\Action\Delete;
use Augias\SupplierBundle\Action\Edit;
use Augias\SupplierBundle\Action\Index;
use Augias\SupplierBundle\Action\View;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_suppliers_index', '/')
        ->controller(Index::class);

    $routingConfigurator
        ->add('_suppliers_add', '/add')
        ->controller(Add::class);

    $routingConfigurator
        ->add('_suppliers_edit', '/edit/{id}')
        ->controller(Edit::class);

    $routingConfigurator
        ->add('_suppliers_view', '/view/{id}')
        ->controller(View::class);

    $routingConfigurator
        ->add('_suppliers_delete', '/delete/{id}')
        ->controller(Delete::class)
        ->methods(['DELETE', 'POST']);
};
