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

namespace SolidInvoice\BillBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\BillBundle\Entity\BillCategory;

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
