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

namespace Augias\BillBundle\Tests\Entity;

use Augias\BillBundle\Entity\BillCategory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BillCategory::class)]
final class BillCategoryTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $category = new BillCategory();
        $category->setName('Supplies');

        self::assertSame('Supplies', $category->getName());
    }

    public function testToStringReturnsName(): void
    {
        $category = new BillCategory();
        $category->setName('Rent');

        self::assertSame('Rent', (string) $category);
    }

    public function testToStringIsEmptyStringWithoutAName(): void
    {
        $category = new BillCategory();

        self::assertSame('', (string) $category);
    }
}
