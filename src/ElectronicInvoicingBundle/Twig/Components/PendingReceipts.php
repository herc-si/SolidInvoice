<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\ElectronicInvoicingBundle\Twig\Components;

use Augias\ElectronicInvoicingBundle\Repository\ElectronicInvoiceReceiptRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Surfaces electronically received invoices on the purchase-invoices page.
 * They are the source a purchase invoice is created from, so they belong in
 * that flow rather than behind a sidebar entry of their own.
 */
#[AsTwigComponent]
final class PendingReceipts
{
    public function __construct(
        private readonly ElectronicInvoiceReceiptRepository $repository,
    ) {
    }

    public function getCount(): int
    {
        return $this->repository->countAwaitingBill();
    }
}
