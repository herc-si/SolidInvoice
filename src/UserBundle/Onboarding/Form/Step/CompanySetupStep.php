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

namespace Augias\UserBundle\Onboarding\Form\Step;

use Augias\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @see \Augias\UserBundle\Tests\Onboarding\Form\Step\CompanySetupStepTest
 * @extends AbstractType<array{companyName: string, companyCurrency: mixed}>
 */
final class CompanySetupStep extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('companyName', TextType::class, [
            'label' => 'onboarding.company.fields.name.label',
            'required' => true,
            'attr' => [
                'placeholder' => 'onboarding.company.fields.name.placeholder',
                'autofocus' => true,
            ],
            'help' => 'onboarding.company.fields.name.help',
        ]);

        $builder->add('companyCurrency', CurrencyType::class, [
            'label' => 'onboarding.company.fields.currency.label',
            'required' => true,
            'help' => 'onboarding.company.fields.currency.help',
        ]);
    }
}
