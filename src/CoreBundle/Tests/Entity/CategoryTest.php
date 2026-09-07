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

namespace Augias\CoreBundle\Tests\Entity;

use Augias\CoreBundle\Entity\Category;
use Augias\CoreBundle\Enum\CategoryUsage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Category::class)]
#[CoversClass(CategoryUsage::class)]
final class CategoryTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $category = new Category()
            ->setName('Matériel')
            ->setUsedForPurchases(true)
            ->setUsedForCatalog(true);

        self::assertSame('Matériel', $category->getName());
        self::assertTrue($category->isUsedForPurchases());
        self::assertTrue($category->isUsedForCatalog());
        self::assertSame('Matériel', (string) $category);
        self::assertNull($category->getId());
    }

    public function testANewCategoryAppliesNowhere(): void
    {
        $category = new Category();

        self::assertFalse($category->supports(CategoryUsage::Purchase));
        self::assertFalse($category->supports(CategoryUsage::Catalog));
        self::assertSame([], $category->usages());
        self::assertFalse($category->hasAtLeastOneUsage());
    }

    public function testUsagesAreIndependent(): void
    {
        $category = new Category()->setUsage(CategoryUsage::Purchase, true);

        self::assertTrue($category->supports(CategoryUsage::Purchase));
        self::assertFalse($category->supports(CategoryUsage::Catalog));
        self::assertSame([CategoryUsage::Purchase], $category->usages());
        self::assertTrue($category->hasAtLeastOneUsage());
    }

    /**
     * The case the merge exists for: one record rather than the same name typed
     * into two separate lists.
     */
    public function testACategoryCanServeBothSides(): void
    {
        $category = new Category()
            ->setUsage(CategoryUsage::Purchase, true)
            ->setUsage(CategoryUsage::Catalog, true);

        self::assertSame(
            [CategoryUsage::Purchase, CategoryUsage::Catalog],
            $category->usages(),
        );
    }

    public function testAUsageCanBeTakenBack(): void
    {
        $category = new Category()
            ->setUsage(CategoryUsage::Purchase, true)
            ->setUsage(CategoryUsage::Catalog, true)
            ->setUsage(CategoryUsage::Purchase, false);

        self::assertSame([CategoryUsage::Catalog], $category->usages());
        self::assertTrue($category->hasAtLeastOneUsage());
    }

    /**
     * The property names are what the repository builds its WHERE clause from
     * and what the form registers its checkboxes under, so they have to match
     * the entity exactly.
     */
    public function testUsagePropertyNamesExistOnTheEntity(): void
    {
        foreach (CategoryUsage::cases() as $usage) {
            self::assertTrue(
                property_exists(Category::class, $usage->propertyName()),
                sprintf('%s::propertyName() names a property Category does not have.', $usage->name),
            );
        }
    }
}
