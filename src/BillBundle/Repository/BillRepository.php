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

namespace Augias\BillBundle\Repository;

use Augias\BillBundle\Entity\Bill;
use Augias\BillBundle\Enum\BillStatus;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<Bill>
 */
class BillRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bill::class);
    }

    /**
     * Pending bills whose due date has passed — candidates for the overdue
     * cron, meant to run with the company filter disabled, the same as
     * {@see \Augias\InvoiceBundle\Repository\InvoiceRepository::getPendingOverdueInvoices()}.
     *
     * @return list<Bill>
     */
    public function getPendingOverdueBills(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.status = :status')
            ->andWhere('b.dueDate IS NOT NULL')
            ->andWhere('b.dueDate < :today')
            ->setParameter('status', BillStatus::Pending->value)
            ->setParameter('today', new DateTimeImmutable('today'))
            ->getQuery()
            ->getResult();
    }

    public function delete(Bill $bill): void
    {
        $this->getEntityManager()->remove($bill);
        $this->getEntityManager()->flush();
    }
}
