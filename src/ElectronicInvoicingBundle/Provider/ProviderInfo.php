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
 * Immutable presentation metadata for a single electronic-invoicing provider.
 *
 * String properties are translation keys, resolved with `|trans` in templates
 * — see Augias\PaymentBundle\Gateway\GatewayInfo, which this mirrors.
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
