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

namespace Augias\PaymentBundle\DependencyInjection\CompilerPass;

use Augias\PaymentBundle\Payum\Storage\DoctrineStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PayumStoragePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('payum.storage.doctrine.orm')) {
            return;
        }

        $definition = $container->getDefinition('payum.storage.doctrine.orm');

        $definition->setClass(DoctrineStorage::class);
    }
}
