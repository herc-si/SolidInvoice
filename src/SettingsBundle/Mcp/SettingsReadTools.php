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

namespace Augias\SettingsBundle\Mcp;

use Augias\CoreBundle\Company\CompanySelector;
use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Repository\CompanyRepository;
use Augias\McpBundle\Mcp\Attribute\McpScopeRequired;
use Augias\McpBundle\Mcp\McpScopeGuard;
use Augias\McpBundle\Security\McpScope;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Symfony\Component\Uid\Ulid;

final readonly class SettingsReadTools
{
    public function __construct(
        private CompanyRepository $companyRepository,
        private CompanySelector $companySelector,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Basic information about the company this token is bound to.
     *
     * @return array{id: string, name: string, currency: string|null}
     */
    #[McpTool(name: 'get_company_info', description: 'Return id, name, and default currency for the active company.')]
    #[McpScopeRequired(McpScope::Read)]
    public function getCompanyInfo(): array
    {
        $this->scopeGuard->require(McpScope::Read);

        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            throw new ToolCallException('No active company on this request.');
        }

        $company = $this->companyRepository->find($companyId);

        if (! $company instanceof Company) {
            throw new ToolCallException('Active company not found.');
        }

        return [
            'id' => $company->getId()->toRfc4122(),
            'name' => $company->getName(),
            'currency' => $company->currency,
        ];
    }
}
