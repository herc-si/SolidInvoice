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

namespace Augias\SaasBundle\Onboarding;

use Augias\CoreBundle\Entity\Company;
use Augias\UserBundle\Entity\User;
use DateTimeImmutable;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;

final readonly class OnboardingContext
{
    public function __construct(
        public User $user,
        public Company $company,
        public Subscription $subscription,
        public Plan $plan,
        public DateTimeImmutable $trialStart,
        public DateTimeImmutable $trialEnd,
    ) {
    }
}
