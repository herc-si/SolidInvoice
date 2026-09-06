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

namespace Augias\ElectronicInvoicingBundle\Provider;

final readonly class ElectronicInvoiceSubmissionResult
{
    public function __construct(
        public bool $success,
        public ?string $externalReference = null,
        public ?string $message = null,
    ) {
    }

    public static function success(?string $externalReference = null, ?string $message = null): self
    {
        return new self(true, $externalReference, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, null, $message);
    }
}
