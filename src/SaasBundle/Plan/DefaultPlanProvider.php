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

namespace Augias\SaasBundle\Plan;

use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;

final readonly class DefaultPlanProvider
{
    public function __construct(
        private PlanRepositoryInterface $planRepository,
    ) {
    }

    public function get(): ?Plan
    {
        return $this->planRepository->findDefault();
    }
}
