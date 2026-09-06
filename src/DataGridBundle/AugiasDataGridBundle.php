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

namespace Augias\DataGridBundle;

use Augias\DataGridBundle\DependencyInjection\AugiasDataGridExtension;
use Override;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AugiasDataGridBundle extends Bundle
{
    public const NAMESPACE = __NAMESPACE__;

    #[Override]
    public function getContainerExtension(): AugiasDataGridExtension
    {
        return new AugiasDataGridExtension();
    }
}
