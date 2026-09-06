<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\BillBundle\Repository;

use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\BillBundle\Entity\Bill;
use SolidInvoice\BillBundle\Enum\BillStatus;
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
     * {@see \SolidInvoice\InvoiceBundle\Repository\InvoiceRepository::getPendingOverdueInvoices()}.
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
