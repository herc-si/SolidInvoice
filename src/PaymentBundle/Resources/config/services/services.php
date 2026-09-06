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

use Augias\PaymentBundle\AugiasPaymentBundle;
use Augias\PaymentBundle\PaymentAction\Offline\StatusAction;
use Augias\PaymentBundle\PaymentAction\PaypalExpress\PaymentDetailsStatusAction;
use Augias\PaymentBundle\Payum\Extension\UpdatePaymentDetailsExtension;
use Payum\Core\Registry\RegistryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$invoiceStateMachine', service('state_machine.invoice'))
    ;

    $services
        ->load(AugiasPaymentBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load('Augias\\PaymentBundle\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    $services
        ->set(PaymentDetailsStatusAction::class)
        ->public()
        ->tag('payum.action', ['factory' => 'paypal_express_checkout', 'prepend' => true]);

    $services
        ->set(StatusAction::class)
        ->public()
        ->tag('payum.action', ['factory' => 'offline']);

    $services
        ->set(UpdatePaymentDetailsExtension::class)
        ->public()
        ->tag('payum.extension', ['all' => true]);

    $services->alias(RegistryInterface::class, 'payum');
};
