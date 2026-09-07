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

use Augias\AccountingBundle\Enum\PeriodType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * How often the company declares — and therefore the granularity its books are
 * closed at, so that a closed period's frozen totals match a return that was
 * actually filed.
 *
 * {@see PeriodType::Year} is left out: no regime here declares annually yet,
 * and offering it would let a user close a whole year in one go and lose the
 * per-return figures.
 *
 * @extends AbstractType<string>
 */
final class DeclarationPeriodicityChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                PeriodType::Month->translationKey() => PeriodType::Month->value,
                PeriodType::Quarter->translationKey() => PeriodType::Quarter->value,
            ],
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
