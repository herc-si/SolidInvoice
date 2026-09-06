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

namespace SolidInvoice\CatalogBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CatalogBundle\Entity\ProductCategory;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<ProductCategory>
 */
final class ProductCategoryRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductCategory::class);
    }

    /**
     * @param list<mixed> $ids
     */
    public function deleteCategories(array $ids): void
    {
        $entityManager = $this->getEntityManager();

        foreach ($ids as $id) {
            $category = $this->find($id);

            if (! $category instanceof ProductCategory) {
                continue;
            }

            $entityManager->remove($category);
        }

        $entityManager->flush();
    }
}
