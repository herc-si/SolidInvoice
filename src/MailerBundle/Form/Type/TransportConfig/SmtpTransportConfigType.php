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

namespace SolidInvoice\MailerBundle\Form\Type\TransportConfig;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @see \SolidInvoice\MailerBundle\Tests\Form\Type\TransportConfig\SmtpTransportConfigTypeTest
 * @extends AbstractType<array{host: mixed, port: int, user: mixed, password: string}>
 */
final class SmtpTransportConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'host',
            null,
            [
                'label' => 'form.field.host',
                'constraints' => new NotBlank(groups: ['smtp']),
            ]
        );

        $builder->add(
            'port',
            IntegerType::class,
            [
                'label' => 'form.field.port',
                'constraints' => new Type(type: 'integer', groups: ['smtp']),
                'required' => false,
            ]
        );

        $builder->add(
            'user',
            null,
            [
                'label' => 'form.field.user',
                'required' => false,
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'label' => 'form.field.password',
                'required' => false,
            ]
        );
    }
}
