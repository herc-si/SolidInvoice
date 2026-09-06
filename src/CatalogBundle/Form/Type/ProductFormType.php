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

namespace SolidInvoice\CatalogBundle\Form\Type;

use Money\Currency;
use SolidInvoice\CatalogBundle\Entity\Product;
use SolidInvoice\CatalogBundle\Entity\ProductCategory;
use SolidInvoice\CatalogBundle\Enum\ProductType;
use SolidInvoice\CatalogBundle\Enum\ProductUnit;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Product>
 */
final class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, ['label' => 'catalog.form.name.label'])
            ->add('reference', null, [
                'label' => 'catalog.form.reference.label',
                'help' => 'catalog.form.reference.help',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'catalog.form.description.label',
                'help' => 'catalog.form.description.help',
                'required' => false,
            ])
            ->add('type', EnumType::class, [
                'label' => 'catalog.form.type.label',
                'class' => ProductType::class,
                'choice_label' => static fn (ProductType $type): string => $type->getLabel(),
                'placeholder' => false,
            ])
            ->add('unit', EnumType::class, [
                'label' => 'catalog.form.unit.label',
                'class' => ProductUnit::class,
                'choice_label' => static fn (ProductUnit $unit): string => $unit->getLabel(),
                'placeholder' => false,
            ])
            ->add('salePrice', MoneyType::class, [
                'label' => 'catalog.form.sale_price.label',
                'currency' => $options['currency'],
            ])
            ->add('purchasePrice', MoneyType::class, [
                'label' => 'catalog.form.purchase_price.label',
                'help' => 'catalog.form.purchase_price.help',
                'currency' => $options['currency'],
                'required' => false,
            ])
            ->add('tax', EntityType::class, [
                'label' => 'catalog.form.tax.label',
                'help' => 'catalog.form.tax.help',
                'class' => Tax::class,
                'choice_label' => 'name',
                'placeholder' => 'catalog.form.tax.placeholder',
                'required' => false,
            ])
            ->add('category', EntityType::class, [
                'label' => 'catalog.form.category.label',
                'class' => ProductCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'catalog.form.category.placeholder',
                'required' => false,
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'catalog.form.active.label',
                'help' => 'catalog.form.active.help',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'currency' => new Currency('EUR'),
        ]);

        $resolver->setAllowedTypes('currency', Currency::class);
    }
}
