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

namespace Augias\ClientBundle\Repository;

use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Enum\ClientStatus;
use Augias\CoreBundle\Util\ArrayUtil;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<Client>
 */
class ClientRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function getTotalClients(?ClientStatus $status = null): int
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('COUNT(c.id)')
            // A pure supplier (isClient = false) isn't a real client and shouldn't
            // count towards client-facing stats or the plan's client limit.
            ->where('c.isClient = true');

        if ($status instanceof ClientStatus) {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', $status->value);
        }

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * Matches an existing client/supplier by one of its tax identifiers'
     * value (e.g. a SIRET from a received electronic invoice), scoped to the
     * given company since tax identifier values aren't globally unique.
     */
    /**
     * Company-scoped on purpose: the incoming-invoice poll runs with the
     * Doctrine company filter disabled so it can walk every company, which
     * means an unscoped lookup here would happily match another tenant's
     * client and attach it as this company's supplier.
     */
    public function findOneByName(Ulid $companyId, string $name): ?Client
    {
        return $this->createQueryBuilder('c')
            ->where('c.company = :companyId')
            ->andWhere('c.name = :name')
            ->setParameter('companyId', $companyId, UlidType::NAME)
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByTaxIdentifierValue(Ulid $companyId, string $value): ?Client
    {
        $qb = $this->createQueryBuilder('c');

        $qb->join('c.taxIdentifiers', 't')
            ->where('c.company = :companyId')
            ->andWhere('t.value = :value')
            ->setParameter('companyId', $companyId, UlidType::NAME)
            ->setParameter('value', $value)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return Client[]
     */
    public function getRecentClients(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select(
            [
                'c.id',
                'c.name',
                'c.created',
                'c.status',
            ]
        )
            ->orderBy('c.created', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getStatusList(): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('DISTINCT c.status');

        return ArrayUtil::column($qb->getQuery()->getResult(), 'status');
    }

    public function getGridQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c');

        return $qb;
    }

    public function getArchivedGridQuery(): QueryBuilder
    {
        $this->getEntityManager()->getFilters()->suspend('archivable');

        $qb = $this->createQueryBuilder('c');

        $qb->select('c')
            ->where('c.archived is not null');

        return $qb;
    }

    /**
     * @param list<int> $ids
     */
    public function archiveClients(array $ids): void
    {
        $em = $this->getEntityManager();

        foreach ($ids as $id) {
            $client = $this->find($id);

            if (! $client instanceof Client) {
                continue;
            }

            $client
                ->setArchived(true)
                ->setStatus(ClientStatus::Archived);

            $em->persist($client);
        }

        $em->flush();
    }

    /**
     * @param list<int> $ids
     */
    public function restoreClients(array $ids): void
    {
        $em = $this->getEntityManager();

        $em->getFilters()->disable('archivable');

        foreach ($ids as $id) {
            $client = $this->find($id);

            if (! $client instanceof Client) {
                continue;
            }

            $client
                ->setArchived(null)
                ->setStatus(ClientStatus::Active);

            $em->persist($client);
        }

        $em->flush();

        $em->getFilters()->enable('archivable');
    }

    /**
     * @param list<string> $ids
     */
    public function deleteClients(array $ids): void
    {
        $em = $this->getEntityManager();

        $em->getFilters()->disable('archivable');

        foreach ($ids as $id) {
            $entity = $this->find($id);

            if (! $entity instanceof Client) {
                continue;
            }

            $em->remove($entity);
        }

        $em->flush();

        $em->getFilters()->enable('archivable');
    }

    /**
     * @param list<int> $ids
     */
    public function removeSupplierRole(array $ids): void
    {
        $em = $this->getEntityManager();

        foreach ($ids as $id) {
            $client = $this->find($id);

            if (! $client instanceof Client) {
                continue;
            }

            $client->setIsSupplier(false);
        }

        $em->flush();
    }

    public function findOneByNameIncludingArchived(string $name): ?Client
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        try {
            return $this->findOneBy(['name' => $name]);
        } finally {
            $filters->enable('archivable');
        }
    }

    public function delete(Client $client): void
    {
        $this->getEntityManager()->remove($client);
        $this->getEntityManager()->flush();
    }
}
