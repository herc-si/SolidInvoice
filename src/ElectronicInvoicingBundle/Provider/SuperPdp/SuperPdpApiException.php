<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdp;

use RuntimeException;
use Throwable;

/**
 * Thrown when the SuperPDP API rejects a request. Carries the `http_ko`
 * payload (see https://api.superpdp.tech/openapi/superpdp.json) so callers
 * can surface a meaningful message without depending on this class's
 * internals.
 */
final class SuperPdpApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $apiCode = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getApiCode(): ?int
    {
        return $this->apiCode;
    }
}
