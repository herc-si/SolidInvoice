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

namespace Augias\AccountingBundle\Form\Type;

use Augias\AccountingBundle\Enum\ActivityNature;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The company's main activity, which seeds the nature of every automatically
 * created revenue entry. Deliberately a plain string choice rather than an
 * `EnumType`: settings are stored and re-read as strings.
 *
 * @extends AbstractType<string>
 */
final class ActivityNatureChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => ActivityNature::choices(),
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
