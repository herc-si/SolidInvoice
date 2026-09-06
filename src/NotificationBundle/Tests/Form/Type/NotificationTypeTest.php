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

namespace Augias\NotificationBundle\Tests\Form\Type;

use Augias\CoreBundle\Tests\FormTestCase;
use Augias\NotificationBundle\Form\Type\NotificationType;

final class NotificationTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'email' => $this->faker->boolean(),
            'sms' => $this->faker->boolean(),
        ];

        $this->assertFormData(NotificationType::class, $formData, json_encode($formData, JSON_THROW_ON_ERROR));
    }
}
