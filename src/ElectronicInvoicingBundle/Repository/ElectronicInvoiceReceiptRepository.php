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

namespace Augias\ElectronicInvoicingBundle\Repository;

use Augias\BillBundle\Entity\Bill;
use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceReceipt;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function sprintf;

/**
 * @extends EntityRepository<ElectronicInvoiceReceipt>
 */
final class ElectronicInvoiceReceiptRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElectronicInvoiceReceipt::class);
    }

    /**
     * Receipts that no {@see Bill} points at yet — the ones still needing a user
     * to act. Conversion state lives on the Bill side only (there is no flag on
     * the receipt), so "pending" has to be expressed as the absence of a Bill.
     * Relies on the company filter for scoping, like any other request-time query.
     */
    public function countAwaitingBill(): int
    {
        $subQuery = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('1')
            ->from(Bill::class, 'b')
            ->where('b.electronicInvoiceReceipt = r')
            ->getDQL();

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where(sprintf('NOT EXISTS (%s)', $subQuery))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * The most recent external_reference already imported for $provider/$companyId,
     * used as the cursor for {@see \Augias\ElectronicInvoicingBundle\Provider\ElectronicInvoiceReceiverInterface::fetchIncoming()}
     * so each poll only asks the provider for invoices newer than what's already
     * been imported. Meant to be called with the company filter disabled, the
     * same way {@see ElectronicInvoiceSubmissionRepository::findPendingByProvider()} is.
     */
    public function findLatestExternalReference(Ulid $companyId, string $provider): ?string
    {
        $receipt = $this->createQueryBuilder('r')
            ->andWhere('r.company = :companyId')
            ->andWhere('r.provider = :provider')
            ->setParameter('companyId', $companyId, UlidType::NAME)
            ->setParameter('provider', $provider)
            ->orderBy('r.created', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $receipt instanceof ElectronicInvoiceReceipt ? $receipt->getExternalReference() : null;
    }

    public function existsForExternalReference(Ulid $companyId, string $provider, string $externalReference): bool
    {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.company = :companyId')
            ->andWhere('r.provider = :provider')
            ->andWhere('r.externalReference = :externalReference')
            ->setParameter('companyId', $companyId, UlidType::NAME)
            ->setParameter('provider', $provider)
            ->setParameter('externalReference', $externalReference)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
