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
 * How often VAT is declared, when that is not the rhythm the books are kept on.
 *
 * Empty — the default — means the two coincide, which is what most companies
 * want and what the module did before this existed.
 *
 * {@see PeriodType::Year} is offered here and nowhere else. The régime réel
 * simplifié wants one VAT return a year while URSSAF still wants turnover every
 * quarter, and that mismatch is the ordinary situation of a micro-entrepreneur
 * who has grown past the franchise threshold — not an edge case. It is safe
 * here because a VAT cycle only groups figures for a return; the books are
 * still sealed on the rhythm above it.
 *
 * @extends AbstractType<string>
 */
final class VatPeriodicityChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'placeholder' => 'accounting.settings.vat_periodicity.same_as_books',
            'choices' => [
                PeriodType::Month->translationKey() => PeriodType::Month->value,
                PeriodType::Quarter->translationKey() => PeriodType::Quarter->value,
                PeriodType::Year->translationKey() => PeriodType::Year->value,
            ],
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
