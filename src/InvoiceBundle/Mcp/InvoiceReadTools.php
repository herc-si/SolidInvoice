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

namespace Augias\InvoiceBundle\Mcp;

use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Repository\ClientRepository;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Augias\McpBundle\Mcp\Attribute\McpScopeRequired;
use Augias\McpBundle\Mcp\McpScopeGuard;
use Augias\McpBundle\Mcp\Tool\EntityNormalizer;
use Augias\McpBundle\Mcp\Tool\UlidParser;
use Augias\McpBundle\Security\McpScope;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Symfony\Bridge\Doctrine\Types\UlidType;

final readonly class InvoiceReadTools
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private ClientRepository $clientRepository,
        private EntityNormalizer $normalizer,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * List overdue invoices for the current company, optionally scoped to a single client.
     *
     * @param string|null $client_id Optional client ULID
     * @param int         $limit     Max rows (1..100)
     *
     * @return array{results: list<array<string, mixed>>, count: int}
     */
    #[McpTool(name: 'list_overdue_invoices', description: 'List overdue invoices, optionally for a specific client.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listOverdueInvoices(?string $client_id = null, int $limit = 25): array
    {
        $this->scopeGuard->require(McpScope::Read);
        $limit = max(1, min($limit, 100));

        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->setParameter('status', InvoiceStatus::Overdue)
            ->orderBy('i.due', 'ASC')
            ->setMaxResults($limit);

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('i.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $results = $this->normalizer->normalizeMany($qb->getQuery()->getResult());

        return ['results' => $results, 'count' => count($results)];
    }

    /**
     * List invoices filtered by status, optionally scoped to a client.
     *
     * @param string      $status    One of: new, draft, pending, paid, active, overdue, cancelled, archived
     * @param string|null $client_id Optional client ULID
     * @param int         $limit     Max rows (1..100)
     *
     * @return array{results: list<array<string, mixed>>, count: int}
     */
    #[McpTool(name: 'list_invoices_by_status', description: 'List invoices filtered by status.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listInvoicesByStatus(string $status, ?string $client_id = null, int $limit = 25): array
    {
        $this->scopeGuard->require(McpScope::Read);
        $limit = max(1, min($limit, 100));

        $statusEnum = InvoiceStatus::tryFrom($status);

        if ($statusEnum === null) {
            throw new ToolCallException(sprintf(
                'Invalid status "%s". Valid values: %s.',
                $status,
                implode(', ', array_map(static fn (InvoiceStatus $s): string => $s->value, InvoiceStatus::cases())),
            ));
        }

        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->setParameter('status', $statusEnum)
            ->orderBy('i.created', 'DESC')
            ->setMaxResults($limit);

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('i.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $results = $this->normalizer->normalizeMany($qb->getQuery()->getResult());

        return ['results' => $results, 'count' => count($results)];
    }
}
