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
 * @see \Augias\MailerBundle\Tests\Form\Type\TransportConfig\SesTransportConfigTypeTest
 * @extends AbstractType<array{accessKey: mixed, accessSecret: string, region: mixed}>
 */
final class SesTransportConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'accessKey',
            null,
            [
                'label' => 'form.field.access_key',
                'constraints' => new NotBlank(groups: ['amazon_ses']),
            ]
        );

        $builder->add(
            'accessSecret',
            PasswordType::class,
            [
                'label' => 'form.field.access_secret',
                'constraints' => new NotBlank(groups: ['amazon_ses']),
            ]
        );

        $builder->add(
            'region',
            null,
            [
                'label' => 'form.field.region',
                'attr' => [
                    'placeholder' => 'eu-west-1',
                ],
            ]
        );
    }
}
