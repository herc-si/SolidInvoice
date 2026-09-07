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

namespace Augias\ElectronicInvoicingBundle\Repository;

use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceSubmission;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<ElectronicInvoiceSubmission>
 */
final class ElectronicInvoiceSubmissionRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElectronicInvoiceSubmission::class);
    }

    /**
     * Successful submissions for $provider that have not yet reached one of
     * $terminalStatusCodes — candidates for a status-polling command. Meant
     * to run with the company filter disabled, so it must be called across
     * every company, not just the current one.
     *
     * @param list<string> $terminalStatusCodes
     *
     * @return list<ElectronicInvoiceSubmission>
     */
    public function findPendingByProvider(string $provider, array $terminalStatusCodes): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.provider = :provider')
            ->andWhere('s.success = true')
            ->andWhere('s.statusCode IS NULL OR s.statusCode NOT IN (:terminalStatusCodes)')
            ->setParameter('provider', $provider)
            ->setParameter('terminalStatusCodes', $terminalStatusCodes)
            ->getQuery()
            ->getResult();
    }
}
