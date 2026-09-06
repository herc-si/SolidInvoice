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

namespace Augias\CoreBundle\Tests\Form\Type;

use Augias\CoreBundle\Entity\Discount;
use Augias\CoreBundle\Form\Type\DiscountType;
use Augias\CoreBundle\Tests\FormTestCase;
use Augias\SettingsBundle\SystemConfig;
use Brick\Math\BigDecimal;
use Generator;
use Mockery as M;
use Money\Currency;
use Override;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;

final class DiscountTypeTest extends FormTestCase
{
    /**
     * @return array<FormExtensionInterface>
     */
    #[Override]
    protected function getExtensions(): array
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig
            ->shouldReceive('getCurrency')
            ->zeroOrMoreTimes()
            ->andReturn(new Currency('USD'));

        return [
            new PreloadedExtension([new DiscountType($systemConfig)], []),
        ];
    }

    public function testSubmit(): void
    {
        foreach ($this->discountProvider() as $discountItem) {
            $formData = [
                'type' => $discountItem[0],
                'value' => $discountItem[1],
            ];

            $object = new Discount();
            $object->setType($discountItem[0]);
            $object->setValue(BigDecimal::of($discountItem[1])->multipliedBy(100));

            $this->assertFormData(DiscountType::class, $formData, $object);
        }
    }

    public function discountProvider(): Generator
    {
        yield [Discount::TYPE_PERCENTAGE, $this->faker->numberBetween(0, 100)];
        yield [Discount::TYPE_MONEY, $this->faker->numberBetween(0, 100)];
    }
}
