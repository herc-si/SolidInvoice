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

use Augias\AccountingBundle\Entity\ThresholdAlert;
use Augias\CoreBundle\Entity\Company;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<ThresholdAlert>
 */
class ThresholdAlertRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ThresholdAlert::class);
    }

    /**
     * Whether this milestone has already been raised this year. Takes the
     * company explicitly because the threshold job runs across tenants with the
     * multi-tenancy filter switched off.
     */
    public function alreadyRaised(Company $company, string $thresholdKey, int $year, int $step): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.company = :company')
            ->andWhere('a.thresholdKey = :key')
            ->andWhere('a.year = :year')
            ->andWhere('a.step = :step')
            ->setParameter('company', $company)
            ->setParameter('key', $thresholdKey)
            ->setParameter('year', $year)
            ->setParameter('step', $step)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * @return list<ThresholdAlert>
     */
    public function findForYear(Company $company, int $year): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.company = :company')
            ->andWhere('a.year = :year')
            ->setParameter('company', $company)
            ->setParameter('year', $year)
            ->orderBy('a.triggeredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
