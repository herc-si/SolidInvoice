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

namespace Augias\AccountingBundle\Repository;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\Declaration;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<Declaration>
 */
class DeclarationRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Declaration::class);
    }

    public function findForPeriod(AccountingPeriod $period): ?Declaration
    {
        return $this->findOneBy(['period' => $period]);
    }
}
