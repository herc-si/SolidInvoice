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

namespace Augias\QuoteBundle\Action;

use Augias\CoreBundle\Form\FieldRenderer;
use Augias\CoreBundle\Traits\JsonTrait;
use Augias\QuoteBundle\Form\Type\QuoteType;
use Money\Currency;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final readonly class Fields
{
    use JsonTrait;

    public function __construct(
        private FormFactoryInterface $factory,
        private FieldRenderer $renderer
    ) {
    }

    public function __invoke(Request $request, string $currency): JsonResponse
    {
        $form = $this->factory->create(QuoteType::class, null, ['currency' => new Currency($currency)]);

        return $this->json($this->renderer->render($form->createView(), 'children[lines].vars[prototype]'));
    }
}
