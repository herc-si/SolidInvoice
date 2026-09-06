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

namespace Augias\CatalogBundle\Repository;

use Augias\CatalogBundle\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<Product>
 */
final class ProductRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @param list<mixed> $ids
     */
    public function deleteProducts(array $ids): void
    {
        $entityManager = $this->getEntityManager();

        foreach ($ids as $id) {
            $product = $this->find($id);

            if (! $product instanceof Product) {
                continue;
            }

            $entityManager->remove($product);
        }

        $entityManager->flush();
    }

    /**
     * Feeds the picker on the invoice and quote line editors: only entries a
     * user can still sell, newest naming first so a rename surfaces quickly.
     *
     * @return list<Product>
     */
    public function findActiveForPicker(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.active = true')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
