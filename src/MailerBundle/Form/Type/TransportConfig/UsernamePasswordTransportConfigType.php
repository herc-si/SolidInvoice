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
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @see \Augias\MailerBundle\Tests\Form\Type\TransportConfig\UsernamePasswordTransportConfigTypeTest
 * @extends AbstractType<array{username: mixed, password: string}>
 */
final class UsernamePasswordTransportConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'username',
            null,
            [
                'label' => 'form.field.username',
                'constraints' => new NotBlank(groups: ['userpass']),
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'label' => 'form.field.password',
                'constraints' => new NotBlank(groups: ['userpass']),
            ]
        );
    }
}
