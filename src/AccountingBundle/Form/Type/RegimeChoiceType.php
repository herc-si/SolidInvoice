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

use Augias\AccountingBundle\Regime\RegimeRegistry;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Picks the company's tax regime from whatever is registered.
 *
 * Choices are built at runtime from {@see RegimeRegistry} rather than baked
 * into the setting's stored form options — those are persisted as JSON when the
 * company is created, so a regime added in a later release would never show up
 * for companies that already exist.
 *
 * @extends AbstractType<string>
 */
final class RegimeChoiceType extends AbstractType
{
    public function __construct(
        private readonly RegimeRegistry $registry,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->registry->choices(),
            'placeholder' => 'accounting.settings.regime.placeholder',
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
