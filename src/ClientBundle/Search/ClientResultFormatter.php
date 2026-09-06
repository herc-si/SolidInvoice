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

namespace Augias\ClientBundle\Search;

use Augias\CoreBundle\Search\QualifiedResultFormatterInterface;
use Augias\CoreBundle\Search\SearchResult;
use Symfony\Component\Routing\RouterInterface;

/**
 * @see \Augias\ClientBundle\Tests\Search\ClientResultFormatterTest
 */
final readonly class ClientResultFormatter implements QualifiedResultFormatterInterface
{
    public function __construct(
        private RouterInterface $router,
    ) {
    }

    public function getSupportedQualifiers(): array
    {
        return ['status' => 'status'];
    }

    public function getIndexName(): string
    {
        return 'clients';
    }

    public function format(array $hit): SearchResult
    {
        return new SearchResult(
            type: 'client',
            id: $hit['id'],
            title: $hit['name'] ?? '',
            subtitle: $hit['website'] ?? '',
            url: $this->router->generate('_clients_view', ['id' => $hit['id']]),
            status: $hit['status'] ?? null,
        );
    }
}
