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

namespace Augias\InvoiceBundle\Message;

use Symfony\Component\Uid\Ulid;

final readonly class MarkInvoiceOverdue
{
    public function __construct(
        private Ulid $invoiceId,
        private Ulid $companyId,
    ) {
    }

    public function getInvoiceId(): Ulid
    {
        return $this->invoiceId;
    }

    public function getCompanyId(): Ulid
    {
        return $this->companyId;
    }
}
