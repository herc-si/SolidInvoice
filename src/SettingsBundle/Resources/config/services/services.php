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

use Augias\SettingsBundle\AugiasSettingsBundle;
use Augias\SettingsBundle\Form\Type\MailTransportType;
use Augias\SettingsBundle\SystemConfig;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->public();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$installed', env('SOLIDINVOICE_INSTALLED'))
        ->bind('$customDomainDnsRecord', env('SOLIDINVOICE_CUSTOM_DOMAIN_DNS_RECORD'))
    ;

    $services
        ->load(AugiasSettingsBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load(AugiasSettingsBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    $services
        ->get(MailTransportType::class)
        ->arg('$transports', tagged_iterator('solidinvoice_mailer.transport.configurator'));

    $services
        ->get(SystemConfig::class);
};
