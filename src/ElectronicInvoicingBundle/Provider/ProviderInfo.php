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

namespace SolidInvoice\ElectronicInvoicingBundle\Provider;

/**
 * Immutable presentation metadata for a single electronic-invoicing provider.
 *
 * String properties are translation keys, resolved with `|trans` in templates
 * — see SolidInvoice\PaymentBundle\Gateway\GatewayInfo, which this mirrors.
 */
final readonly class ProviderInfo
{
    public function __construct(
        public string $name,
        public string $displayName,
        public string $tagline,
        public string $icon,
        public bool $recommended = false,
    ) {
    }
}
