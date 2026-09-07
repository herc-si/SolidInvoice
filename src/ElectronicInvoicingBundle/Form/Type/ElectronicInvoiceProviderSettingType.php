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

namespace Augias\ElectronicInvoicingBundle\Form\Type;

use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;
use Augias\ElectronicInvoicingBundle\Provider\ElectronicInvoiceProviderInterface;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * @extends AbstractType<ElectronicInvoiceProviderSetting>
 */
final class ElectronicInvoiceProviderSettingType extends AbstractType
{
    /**
     * @param ServiceLocator<ElectronicInvoiceProviderInterface> $providers
     */
    public function __construct(
        #[AutowireLocator(ElectronicInvoiceProviderInterface::DI_TAG)]
        private readonly ServiceLocator $providers,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder->add('name');

        $builder->add('provider', HiddenType::class);

        $builder->addDependent('settings', 'provider', function (DependentField $field, ?string $provider): void {
            if ($provider === null || ! $this->providers->has($provider)) {
                return;
            }

            $field->add($this->providers->get($provider)->getForm());
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ElectronicInvoiceProviderSetting::class]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'einvoicing_provider_setting';
    }
}
