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

use Augias\TaxBundle\Action\Add;
use Augias\TaxBundle\Action\Edit;
use Augias\TaxBundle\Action\Index;
use Augias\TaxBundle\Action\Validate;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_tax_rates', '/rates')
        ->controller(Index::class);

    $routingConfigurator
        ->add('_tax_rates_add', '/rates/add')
        ->controller(Add::class);

    $routingConfigurator
        ->add('_tax_rates_edit', '/rates/edit/{id}')
        ->controller(Edit::class);

    $routingConfigurator
        ->add('_tax_number_validate', '/number/validate')
        ->controller(Validate::class)
        ->methods(['POST']);
};
