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

namespace Augias\CoreBundle\Company;

use Augias\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * @see \Augias\CoreBundle\Tests\Company\AllUserCompaniesTest
 */
#[AsAlias(id: UserEligibleCompanies::class)]
final class AllUserCompanies implements UserEligibleCompanies
{
    public function getFor(User $user): array
    {
        return array_values($user->getCompanies()->toArray());
    }
}
