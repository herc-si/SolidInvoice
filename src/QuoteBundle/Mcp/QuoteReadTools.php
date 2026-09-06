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

namespace Augias\QuoteBundle\Mcp;

use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Repository\ClientRepository;
use Augias\McpBundle\Mcp\Attribute\McpScopeRequired;
use Augias\McpBundle\Mcp\McpScopeGuard;
use Augias\McpBundle\Mcp\Tool\EntityNormalizer;
use Augias\McpBundle\Mcp\Tool\UlidParser;
use Augias\McpBundle\Security\McpScope;
use Augias\QuoteBundle\Enum\QuoteStatus;
use Augias\QuoteBundle\Repository\QuoteRepository;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Symfony\Bridge\Doctrine\Types\UlidType;

final readonly class QuoteReadTools
{
    public function __construct(
        private QuoteRepository $quoteRepository,
        private ClientRepository $clientRepository,
        private EntityNormalizer $normalizer,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * List quotes filtered by status, optionally scoped to a client.
     *
     * @param string      $status    One of: new, draft, pending, accepted, cancelled, declined, archived
     * @param string|null $client_id Optional client ULID
     * @param int         $limit     Max rows (1..100)
     *
     * @return array{results: list<array<string, mixed>>, count: int}
     */
    #[McpTool(name: 'list_quotes_by_status', description: 'List quotes filtered by status.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listQuotesByStatus(string $status, ?string $client_id = null, int $limit = 25): array
    {
        $this->scopeGuard->require(McpScope::Read);
        $limit = max(1, min($limit, 100));

        $statusEnum = QuoteStatus::tryFrom($status);

        if ($statusEnum === null) {
            throw new ToolCallException(sprintf(
                'Invalid status "%s". Valid values: %s.',
                $status,
                implode(', ', array_map(static fn (QuoteStatus $s): string => $s->value, QuoteStatus::cases())),
            ));
        }

        $qb = $this->quoteRepository->createQueryBuilder('q')
            ->andWhere('q.status = :status')
            ->setParameter('status', $statusEnum)
            ->orderBy('q.created', 'DESC')
            ->setMaxResults($limit);

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('q.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $results = $this->normalizer->normalizeMany($qb->getQuery()->getResult());

        return ['results' => $results, 'count' => count($results)];
    }
}
