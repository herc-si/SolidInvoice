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

namespace Augias\CoreBundle\Form\Type;

use Augias\CoreBundle\Entity\Category;
use Augias\CoreBundle\Enum\CategoryUsage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \Augias\CoreBundle\Tests\Form\Type\CategoryTypeTest
 * @extends AbstractType<Category>
 */
final class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', null, ['label' => 'category.form.name.label']);

        // One checkbox per usage rather than a multi-select: there are two, and
        // a category that applies to both is the normal case the merge exists
        // to support, not an edge one worth hiding behind a picker.
        foreach (CategoryUsage::cases() as $usage) {
            $builder->add(
                $usage->propertyName(),
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => $usage->translationKey(),
                    'help' => 'category.form.usage.' . $usage->value . '.help',
                    'label_attr' => ['class' => 'switch-custom'],
                ],
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Category::class]);
    }
}
