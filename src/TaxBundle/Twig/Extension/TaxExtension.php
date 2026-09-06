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

namespace Augias\TaxBundle\Twig\Extension;

use Augias\TaxBundle\Entity\Tax;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TaxExtension extends AbstractExtension
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('taxRatesConfigured', fn (): bool => $this->taxRatesConfigured()),
        ];
    }

    public function taxRatesConfigured(): bool
    {
        static $taxConfigured;

        return $taxConfigured ?? ($taxConfigured = $this->registry->getRepository(Tax::class)->taxRatesConfigured());
    }
}
