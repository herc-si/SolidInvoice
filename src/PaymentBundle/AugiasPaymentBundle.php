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

namespace Augias\PaymentBundle;

use Augias\PaymentBundle\DependencyInjection\CompilerPass\PayumStoragePass;
use Augias\PaymentBundle\DependencyInjection\PaymentExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AugiasPaymentBundle extends Bundle
{
    public const NAMESPACE = __NAMESPACE__;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PayumStoragePass());
        $container->registerExtension(new PaymentExtension());
    }
}
