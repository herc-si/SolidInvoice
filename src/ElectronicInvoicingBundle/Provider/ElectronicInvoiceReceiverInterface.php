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

namespace Augias\ElectronicInvoicingBundle\Provider;

/**
 * Implemented by an {@see ElectronicInvoiceProviderInterface} that can also
 * report invoices received BY this company, not just deliver ones it sends.
 *
 * Deliberately a separate, optional interface rather than new methods on
 * ElectronicInvoiceProviderInterface: sending and receiving are independent
 * capabilities — a platform (or a company's subscription to it) may support
 * only one of the two, and every existing/future provider that only sends
 * shouldn't be forced to implement reception at all.
 *
 * @see ElectronicInvoiceProviderRegistry::getReceiver()
 */
interface ElectronicInvoiceReceiverInterface
{
    /**
     * Lists invoices received by this company since $afterExternalReference
     * (exclusive), oldest first — null means "from the beginning". Providers
     * decide their own pagination/cursor semantics internally; this method
     * returns one page's worth, and the caller polls again for more.
     *
     * @param array<string, mixed> $config
     *
     * @return list<ReceivedElectronicInvoiceData>
     */
    public function fetchIncoming(array $config, ?string $afterExternalReference): array;

    /**
     * Downloads the raw document (PDF/XML) for one previously-listed invoice,
     * identified by the $externalReference from ReceivedElectronicInvoiceData.
     *
     * @param array<string, mixed> $config
     */
    public function downloadIncomingDocument(array $config, string $externalReference): DownloadedElectronicInvoiceDocument;
}
