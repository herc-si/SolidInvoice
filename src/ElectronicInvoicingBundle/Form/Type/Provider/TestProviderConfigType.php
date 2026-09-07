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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array{reference_prefix: mixed, simulate_failure: mixed}>
 */
final class TestProviderConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('reference_prefix', TextType::class, [
            'label' => 'einvoicing.provider.test.reference_prefix',
            'help' => 'einvoicing.provider.test.reference_prefix_help',
            'constraints' => new NotBlank(groups: ['test_provider']),
        ]);

        $builder->add('simulate_failure', CheckboxType::class, [
            'label' => 'einvoicing.provider.test.simulate_failure',
            'required' => false,
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'test_provider_config';
    }
}
