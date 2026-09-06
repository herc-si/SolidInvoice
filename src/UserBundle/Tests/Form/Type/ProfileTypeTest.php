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

namespace Augias\UserBundle\Tests\Form\Type;

use Augias\CoreBundle\Tests\FormTestCase;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Form\Type\ProfileType;

final class ProfileTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $mobile = $this->faker->phoneNumber();

        $formData = [
            'firstName' => $this->faker->firstName(),
            'lastName' => $this->faker->lastName(),
            'mobile' => $mobile,
        ];

        $object = new User();
        $object->setMobile($mobile);

        $this->assertFormData(ProfileType::class, $formData, $object);
    }
}
