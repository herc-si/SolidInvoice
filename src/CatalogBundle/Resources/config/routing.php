<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use SolidInvoice\CatalogBundle\Action\Add;
use SolidInvoice\CatalogBundle\Action\Category\Add as CategoryAdd;
use SolidInvoice\CatalogBundle\Action\Category\Edit as CategoryEdit;
use SolidInvoice\CatalogBundle\Action\Category\Index as CategoryIndex;
use SolidInvoice\CatalogBundle\Action\Edit;
use SolidInvoice\CatalogBundle\Action\Index;
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

    $routingConfigurator
        ->add('_catalog_categories_index', '/categories')
        ->controller(CategoryIndex::class);

    $routingConfigurator
        ->add('_catalog_categories_add', '/categories/add')
        ->controller(CategoryAdd::class);

    $routingConfigurator
        ->add('_catalog_categories_edit', '/categories/edit/{id}')
        ->controller(CategoryEdit::class);
};
