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

/**
 * The raw document behind a {@see ReceivedElectronicInvoiceData} entry, fetched
 * on demand once — {@see \Augias\ElectronicInvoicingBundle\Manager\ElectronicInvoiceReceiptManager}
 * writes its $content to local storage so later downloads don't need to call
 * back out to the provider.
 */
final readonly class DownloadedElectronicInvoiceDocument
{
    public function __construct(
        public string $content,
        public string $mimeType,
        public string $fileExtension,
    ) {
    }
}
