<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\SupplierBundle\Action;

use Symfony\Bridge\Twig\Attribute\Template;

final class Index
{
    /**
     * @return array{}
     */
    #[Template('@AugiasSupplier/Default/index.html.twig')]
    public function __invoke(): array
    {
        return [];
    }
}
