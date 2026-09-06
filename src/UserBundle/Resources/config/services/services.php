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

use Augias\UserBundle\AugiasUserBundle;
use Augias\UserBundle\Repository\UserRepository;
use Augias\UserBundle\Repository\UserRepositoryInterface;
use Augias\UserBundle\Repository\UserSettingRepository;
use Augias\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidWorx\Platform\PlatformBundle\Contracts\Doctrine\Repository\UserRepository as PlatformUserRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private();

    $services
        ->load(AugiasUserBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load(AugiasUserBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    $services->alias(UserRepositoryInterface::class, UserRepository::class);
    $services->alias(PlatformUserRepository::class, UserRepository::class);
    $services->alias(UserSettingRepositoryInterface::class, UserSettingRepository::class);

    $services
        ->load(AugiasUserBundle::NAMESPACE . '\\DataFixtures\\ORM\\', dirname(__DIR__, 3) . '/DataFixtures/ORM/*')
        ->tag('doctrine.fixture.orm');
};
