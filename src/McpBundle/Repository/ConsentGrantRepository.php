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

namespace Augias\McpBundle\Repository;

use Augias\CoreBundle\Entity\Company;
use Augias\McpBundle\Entity\ConsentGrant;
use Augias\McpBundle\Entity\OAuthClient;
use Augias\UserBundle\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<ConsentGrant>
 */
final class ConsentGrantRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConsentGrant::class);
    }

    public function findGrant(OAuthClient $client, User $user, Company $company): ?ConsentGrant
    {
        return $this->findOneBy([
            'client' => $client,
            'user' => $user,
            'company' => $company,
        ]);
    }

    /**
     * Find the most recent consent grant for a client+user pair, across any
     * company they might have. Used by the token-issuance flow to resolve the
     * bound company from the consent that authorised this client.
     */
    public function findGrantForClientUser(OAuthClient $client, User $user): ?ConsentGrant
    {
        return $this->findOneBy(
            ['client' => $client, 'user' => $user],
            ['created' => 'DESC'],
        );
    }
}
