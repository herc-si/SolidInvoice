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

namespace Augias\BillBundle\Tests\Form\Type;

use Augias\BillBundle\Entity\Bill;
use Augias\BillBundle\Form\Type\BillType;
use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\CoreBundle\Tests\FormTestCase;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Money\Currency;

/**
 * @see \Augias\BillBundle\Form\Type\BillType
 */
final class BillTypeTest extends FormTestCase
{
    use EnsureApplicationInstalled;

    public function testSubmit(): void
    {
        $supplier = ClientFactory::createOne(['company' => $this->company, 'isClient' => false, 'isSupplier' => true]);

        $formData = [
            'supplier' => (string) $supplier->getId(),
            'newSupplierName' => '',
            'billNumber' => 'SUP-0001',
            'currencyCode' => 'USD',
            'totalAmount' => '100',
        ];

        $form = $this->factory->create(BillType::class, new Bill(), ['currency' => new Currency('USD')]);
        $form->submit($formData, false);

        self::assertTrue($form->isSynchronized());
        self::assertCount(0, $form->get('supplier')->getErrors());
        // `supplier` is unmapped — the Action reads it from the form directly
        // and calls Bill::setSupplier() itself, rather than the form doing it,
        // since the "create a new supplier inline" path can't be expressed as
        // a plain property mapping (see BillType::buildForm()).
        self::assertSame($supplier, $form->get('supplier')->getData());
    }

    public function testSubmitFailsWithoutAnExistingOrANewSupplier(): void
    {
        $formData = [
            'supplier' => '',
            'newSupplierName' => '',
            'billNumber' => 'SUP-0001',
            'currencyCode' => 'USD',
            'totalAmount' => '100',
        ];

        $form = $this->factory->create(BillType::class, new Bill(), ['currency' => new Currency('USD')]);
        $form->submit($formData, false);

        self::assertGreaterThan(0, $form->get('supplier')->getErrors()->count());
    }

    public function testSubmitAcceptsANewSupplierNameWithoutAnExistingSupplierSelected(): void
    {
        $formData = [
            'supplier' => '',
            'newSupplierName' => 'Brand New Supplier Inc.',
            'billNumber' => 'SUP-0001',
            'currencyCode' => 'USD',
            'totalAmount' => '100',
        ];

        $form = $this->factory->create(BillType::class, new Bill(), ['currency' => new Currency('USD')]);
        $form->submit($formData, false);

        self::assertCount(0, $form->get('supplier')->getErrors());
        self::assertSame('Brand New Supplier Inc.', $form->get('newSupplierName')->getData());
        self::assertNull($form->get('supplier')->getData());
    }
}
