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

namespace Augias\PaymentBundle\Mcp;

use Augias\McpBundle\Mcp\Attribute\McpScopeRequired;
use Augias\McpBundle\Mcp\McpScopeGuard;
use Augias\McpBundle\Mcp\Tool\EntityNormalizer;
use Augias\McpBundle\Security\McpScope;
use Augias\PaymentBundle\Repository\PaymentMethodRepository;
use Mcp\Capability\Attribute\McpTool;

final readonly class PaymentMethodReadTools
{
    public function __construct(
        private PaymentMethodRepository $repository,
        private EntityNormalizer $normalizer,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * List payment methods configured for the current company.
     *
     * @return array{results: list<array<string, mixed>>, count: int}
     */
    #[McpTool(name: 'list_payment_methods', description: 'List configured payment methods for the current company.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listPaymentMethods(): array
    {
        $this->scopeGuard->require(McpScope::Read);

        $methods = $this->repository->createQueryBuilder('m')
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();

        $results = $this->normalizer->normalizeMany($methods);

        return ['results' => $results, 'count' => count($results)];
    }
}
