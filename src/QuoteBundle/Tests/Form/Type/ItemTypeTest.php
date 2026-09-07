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

namespace Augias\QuoteBundle\Tests\Form\Type;

use Augias\CoreBundle\Tests\FormTestCase;
use Augias\QuoteBundle\Entity\Line;
use Augias\QuoteBundle\Form\Type\ItemType;
use Augias\TaxBundle\Service\TaxAvailability;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Money\Currency;
use Override;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;

final class ItemTypeTest extends FormTestCase
{
    /**
     * @throws MathException
     */
    public function testSubmit(): void
    {
        $description = $this->faker->text();
        $price = $this->faker->randomNumber(3);
        $qty = '12.345678';

        $formData = [
            'description' => $description,
            'price' => $price,
            'qty' => $qty,
        ];

        $currency = new Currency('USD');

        $object = new Line();
        $object->setDescription($description);
        $object->setQty($qty);
        $object->setPrice(BigDecimal::of($price)->multipliedBy(100));

        $this->assertFormData($this->factory->create(ItemType::class, null, ['currency' => $currency]), $formData, $object);
    }

    /**
     * @throws MathException
     */
    public function testSubmitPreservesSpecialCharacters(): void
    {
        $description = 'Item + discount & "special" chars: 100% off';
        $price = 100;
        $qty = '1';

        $formData = [
            'description' => $description,
            'price' => $price,
            'qty' => $qty,
        ];

        $currency = new Currency('USD');

        $object = new Line();
        $object->setDescription($description);
        $object->setQty($qty);
        $object->setPrice(BigDecimal::of($price)->multipliedBy(100));

        $this->assertFormData($this->factory->create(ItemType::class, null, ['currency' => $currency]), $formData, $object);
    }

    /**
     * @return array<FormExtensionInterface>
     */
    #[Override]
    protected function getExtensions(): array
    {
        $itemType = new ItemType($this->taxAvailability());

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$itemType], []),
        ];
    }

    /**
     * Whether the tax field is offered is a service now, joining "any rates
     * configured" with "the company is liable for VAT". These tests are about
     * the liable case, so it comes from the container rather than a stub.
     */
    private function taxAvailability(): TaxAvailability
    {
        $availability = self::getContainer()->get(TaxAvailability::class);
        self::assertInstanceOf(TaxAvailability::class, $availability);

        return $availability;
    }
}
