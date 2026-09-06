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

namespace Augias\CoreBundle\Twig\Extension;

use Augias\CoreBundle\Form\FieldRenderer;
use Augias\MoneyBundle\Calculator;
use Brick\Math\BigNumber;
use Override;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BillingExtension extends AbstractExtension
{
    public function __construct(
        private readonly FieldRenderer $fieldRenderer,
        private readonly Calculator $calculator
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('billing_fields', fn (FormView $form) => $this->fieldRenderer->render($form, 'children[lines].vars[prototype]'), ['is_safe' => ['html']]),
            new TwigFunction('discount', fn ($entity): BigNumber => $this->calculator->calculateDiscount($entity)),
        ];
    }
}
