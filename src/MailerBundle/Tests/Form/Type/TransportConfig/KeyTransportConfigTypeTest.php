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

namespace Augias\MailerBundle\Tests\Form\Type\TransportConfig;

use Augias\CoreBundle\Tests\FormTestCase;
use Augias\MailerBundle\Form\Type\TransportConfig\KeyTransportConfigType;

final class KeyTransportConfigTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'key' => 'foobar',
        ];

        $this->assertFormData(KeyTransportConfigType::class, $formData, ['key' => 'foobar']);
    }
}
