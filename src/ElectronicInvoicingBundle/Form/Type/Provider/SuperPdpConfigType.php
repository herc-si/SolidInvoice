<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\ElectronicInvoicingBundle\Form\Type\Provider;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * OAuth2 client_credentials for the SUPER PDP API (https://www.superpdp.tech)
 * — the company itself is enrolled on the platform outside Augias.
 *
 * @extends AbstractType<array{client_id: string, client_secret: string}>
 */
final class SuperPdpConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('client_id', TextType::class, [
            'label' => 'einvoicing.provider.super_pdp.client_id',
            'constraints' => new NotBlank(groups: ['super_pdp']),
        ]);

        $builder->add('client_secret', PasswordType::class, [
            'label' => 'einvoicing.provider.super_pdp.client_secret',
            'constraints' => new NotBlank(groups: ['super_pdp']),
            'always_empty' => false,
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'super_pdp_config';
    }
}
