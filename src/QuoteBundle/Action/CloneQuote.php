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

use Augias\CoreBundle\Response\FlashResponse;
use Augias\QuoteBundle\Cloner\QuoteCloner;
use Augias\QuoteBundle\Entity\Quote;
use Generator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final readonly class CloneQuote
{
    public function __construct(
        private QuoteCloner $cloner,
        private RouterInterface $router
    ) {
    }

    public function __invoke(Request $request, Quote $quote): RedirectResponse
    {
        $newQuote = $this->cloner->clone($quote);

        $route = $this->router->generate('_quotes_view', ['id' => $newQuote->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'quote.clone.success';
            }
        };
    }
}
