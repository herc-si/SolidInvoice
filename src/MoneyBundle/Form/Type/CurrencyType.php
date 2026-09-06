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

namespace Augias\MoneyBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Generator;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CurrencyType extends AbstractType
{
    public function __construct(
        private readonly string $locale
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => iterator_to_array($this->getCurrencyChoices()),
            // Choice labels are currency names from Symfony's Intl component,
            // already localized via Currencies::getNames($this->locale) below —
            // routing them through the app's own translator too would just
            // look up the (already-translated) name as an id nothing defines.
            'choice_translation_domain' => false,
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    /**
     * @return Generator<string, string>
     */
    private function getCurrencyChoices(): Generator
    {
        $currencyList = Currencies::getNames($this->locale);

        $collection = new ArrayCollection(iterator_to_array(new ISOCurrencies()->getIterator()))
            ->filter(fn (Currency $currency): bool => array_key_exists($currency->getCode(), $currencyList));

        foreach ($collection as $currency) {
            if (empty($currency->getCode())) {
                continue;
            }

            yield $currencyList[$currency->getCode()] => $currency->getCode();
        }
    }
}
