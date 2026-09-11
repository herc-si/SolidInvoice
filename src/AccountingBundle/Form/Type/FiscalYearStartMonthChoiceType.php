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

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function array_combine;
use function array_map;
use function range;
use function sprintf;

/**
 * The month the financial year opens on.
 *
 * January for most, and for a French micro-entrepreneur always: their exercice
 * is the calendar year by law. It is offered all the same because the module is
 * not only for them.
 *
 * Month names are translation keys rather than formatted dates: the setting is
 * stored once and read on every screen, and a name formatted in whatever locale
 * the saving user happened to have would be wrong for everyone else.
 *
 * @extends AbstractType<string>
 */
final class FiscalYearStartMonthChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $months = range(1, 12);

        $resolver->setDefaults([
            'choices' => array_combine(
                array_map(static fn (int $month): string => sprintf('accounting.month.%d', $month), $months),
                array_map(strval(...), $months),
            ),
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
