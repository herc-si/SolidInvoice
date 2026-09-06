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

namespace Augias\MailerBundle\Form\Type\TransportConfig;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @see \Augias\MailerBundle\Tests\Form\Type\TransportConfig\KeyTransportConfigTypeTest
 * @extends AbstractType<array{key: mixed}>
 */
final class KeyTransportConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'key',
            null,
            [
                'label' => 'form.field.key',
                'constraints' => new NotBlank(groups: ['key']),
            ]
        );
    }
}
