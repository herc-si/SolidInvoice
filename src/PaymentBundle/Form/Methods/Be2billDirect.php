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
 * @extends AbstractType<array{identifier: string, password: string, sandbox: bool}>
 */
class Be2billDirect extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'identifier',
            TextType::class,
            [
                'label' => 'form.field.identifier',
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'label' => 'form.field.password',
                'constraints' => new NotBlank(),
                'always_empty' => false,
            ]
        );

        $builder->add(
            'sandbox',
            CheckboxType::class,
            [
                'label' => 'form.field.sandbox',
                'required' => false,
            ]
        );
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'be2bill_direct';
    }
}
