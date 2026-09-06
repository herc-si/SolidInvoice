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

namespace Augias\PaymentBundle\Tests\Form\Type;

use Augias\CoreBundle\Tests\FormTestCase;
use Augias\PaymentBundle\Form\Type\PaymentMethodType;

final class PaymentMethodTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $name = $this->faker->name();
        $enabled = $this->faker->boolean();
        $internal = $this->faker->boolean();

        $formData = [
            'name' => $name,
            'enabled' => $enabled,
            'internal' => $internal,
        ];

        $object = [
            'name' => $name,
            'enabled' => $enabled,
            'internal' => $internal,
        ];

        $this->assertFormData($this->factory->create(PaymentMethodType::class, [], ['config' => null]), $formData, $object);
    }
}
