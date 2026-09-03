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

use SolidInvoice\ElectronicInvoicingBundle\Action\Providers;
use SolidInvoice\ElectronicInvoicingBundle\Action\SendElectronicInvoice;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_einvoicing_providers', '/providers')
        ->controller(Providers::class)
        ->methods(['GET']);

    $routingConfigurator
        ->add('_einvoicing_send', '/send/{id}')
        ->controller(SendElectronicInvoice::class);
};
