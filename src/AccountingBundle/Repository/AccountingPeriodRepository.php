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
use Augias\AccountingBundle\Enum\PeriodStatus;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\CoreBundle\Entity\Company;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends EntityRepository<AccountingPeriod>
 */
class AccountingPeriodRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountingPeriod::class);
    }

    public function findForDate(Company $company, PeriodType $type, DateTimeImmutable $date): ?AccountingPeriod
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.type = :type')
            ->andWhere('p.year = :year')
            ->andWhere('p.ordinal = :ordinal')
            ->setParameter('company', $company->getId(), UlidType::NAME)
            ->setParameter('type', $type->value)
            ->setParameter('year', (int) $date->format('Y'))
            ->setParameter('ordinal', $type->ordinalOf($date))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Whether anything before this period is still open. Closing out of order
     * would leave a hole in the sequence numbering and break the hash chain, so
     * {@see \Augias\AccountingBundle\Service\AccountingPeriodManager::close()}
     * refuses when this is true.
     */
    public function hasOpenPeriodBefore(AccountingPeriod $period): bool
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.company = :company')
            ->andWhere('p.type = :type')
            ->andWhere('p.status = :status')
            ->andWhere('p.endDate < :start')
            ->setParameter('company', $period->getCompany()->getId(), UlidType::NAME)
            ->setParameter('type', $period->getType()->value)
            ->setParameter('status', PeriodStatus::Open->value)
            ->setParameter('start', $period->getStartDate())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * The earliest period still accepting entries. Used to file an entry whose
     * own period has already been closed, so a late payment lands somewhere
     * real instead of being dropped.
     */
    public function findEarliestOpenPeriod(Company $company, PeriodType $type): ?AccountingPeriod
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.type = :type')
            ->andWhere('p.status = :status')
            ->setParameter('company', $company->getId(), UlidType::NAME)
            ->setParameter('type', $type->value)
            ->setParameter('status', PeriodStatus::Open->value)
            ->orderBy('p.startDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<AccountingPeriod>
     */
    public function findForYear(Company $company, PeriodType $type, int $year): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.type = :type')
            ->andWhere('p.year = :year')
            ->setParameter('company', $company->getId(), UlidType::NAME)
            ->setParameter('type', $type->value)
            ->setParameter('year', $year)
            ->orderBy('p.ordinal', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Closed periods that have no declaration yet — what the periodic job picks
     * up to prepare a return.
     *
     * @return list<AccountingPeriod>
     */
    public function findClosedWithoutDeclaration(Company $company): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin(
                'Augias\AccountingBundle\Entity\Declaration',
                'd',
                'WITH',
                'd.period = p',
            )
            ->andWhere('p.company = :company')
            ->andWhere('p.status = :status')
            ->andWhere('d.id IS NULL')
            ->setParameter('company', $company->getId(), UlidType::NAME)
            ->setParameter('status', PeriodStatus::Closed->value)
            ->orderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
