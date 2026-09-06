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

namespace Augias\PaymentBundle\Form\Methods;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array{login_id: string, transaction_key: string, hash_secret: string, test_mode: bool}>
 */
class AuthorizeNetSim extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'login_id',
            TextType::class,
            [
                'label' => 'form.field.login_id',
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'transaction_key',
            TextType::class,
            [
                'label' => 'form.field.transaction_key',
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'hash_secret',
            PasswordType::class,
            [
                'label' => 'form.field.hash_secret',
                'constraints' => new NotBlank(),
                'always_empty' => false,
            ]
        );

        $builder->add(
            'test_mode',
            CheckboxType::class,
            [
                'label' => 'form.field.test_mode',
                'required' => false,
            ]
        );
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'authorizenet_sim';
    }
}
