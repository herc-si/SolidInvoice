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

namespace Augias\McpBundle\Mcp;

use Augias\McpBundle\Security\McpScope;
use Mcp\Exception\ToolCallException;

/**
 * Enforces that the current MCP request carries the required OAuth scope.
 * Tools call {@see require()} at the top of every handler method.
 */
final readonly class McpScopeGuard
{
    public function __construct(
        private McpSecurityContext $context,
    ) {
    }

    /**
     * @throws ToolCallException if the current token lacks the required scope
     */
    public function require(McpScope $required): void
    {
        $granted = $this->context->getScopes();

        if (! McpScope::satisfies($granted, $required)) {
            throw new ToolCallException(sprintf(
                'This tool requires the "%s" scope. Granted: %s.',
                $required->value,
                $granted === [] ? '(none)' : implode(', ', $granted),
            ));
        }
    }
}
