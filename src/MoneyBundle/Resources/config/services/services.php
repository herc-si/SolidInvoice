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

use Augias\MoneyBundle\AugiasMoneyBundle;
use Augias\MoneyBundle\Formatter\MoneyFormatter;
use Augias\MoneyBundle\Formatter\MoneyFormatterInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$locale', env('SOLIDINVOICE_LOCALE'))
        ->bind('$normalizer', service('api_platform.serializer.normalizer.item'))
    ;

    $services
        ->load(AugiasMoneyBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services->alias(MoneyFormatterInterface::class, MoneyFormatter::class);
};
