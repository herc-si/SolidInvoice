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

namespace Augias\ApiBundle\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Augias\QuoteBundle\Entity\Quote;
use Augias\QuoteBundle\Repository\QuoteRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<Quote> */
final readonly class QuoteItemProvider implements ProviderInterface
{
    public function __construct(
        private QuoteRepository $repository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Quote
    {
        $quote = $this->repository->findOneBy(['id' => $uriVariables['id']]);

        if (! $quote instanceof Quote) {
            throw new NotFoundHttpException(sprintf('Quote "%s" not found.', $uriVariables['id']));
        }

        return $quote;
    }
}
