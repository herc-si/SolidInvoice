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

use function preg_replace;
use function str_replace;
use function ucwords;

/**
 * Central registry of presentation metadata for electronic-invoicing
 * providers, so the marketplace and configuration screens read their
 * labels/icons from one place instead of duplicating it in the Live
 * Components — see Augias\PaymentBundle\Gateway\GatewayMetadataProvider,
 * which this mirrors.
 */
final class ProviderMetadataProvider
{
    /**
     * @var array<string, ProviderInfo>|null
     */
    private ?array $metadata = null;

    public function get(string $name): ProviderInfo
    {
        return $this->find($name) ?? $this->fallback($name);
    }

    public function find(string $name): ?ProviderInfo
    {
        return $this->metadata()[$name] ?? null;
    }

    /**
     * @return array<string, ProviderInfo>
     */
    private function metadata(): array
    {
        if ($this->metadata !== null) {
            return $this->metadata;
        }

        $providers = [
            new ProviderInfo(
                name: 'super_pdp',
                displayName: 'einvoicing.provider.super_pdp.display_name',
                tagline: 'einvoicing.provider.super_pdp.tagline',
                icon: 'tabler:file-invoice',
                recommended: true,
            ),
            new ProviderInfo(
                name: 'test_provider',
                displayName: 'einvoicing.provider.test.display_name',
                tagline: 'einvoicing.provider.test.tagline',
                icon: 'tabler:flask',
            ),
        ];

        $this->metadata = [];
        foreach ($providers as $provider) {
            $this->metadata[$provider->name] = $provider;
        }

        return $this->metadata;
    }

    private function fallback(string $name): ProviderInfo
    {
        return new ProviderInfo(
            name: $name,
            displayName: $this->humanize($name),
            tagline: 'einvoicing.provider._fallback.tagline',
            icon: 'tabler:building-broadcast-tower',
        );
    }

    private function humanize(string $name): string
    {
        // Sanitize the provider name to prevent XSS - only keep alphanumeric, underscores and hyphens.
        $sanitized = preg_replace('/[^a-z0-9_-]/i', '', $name);

        return ucwords(str_replace(['_', '-'], ' ', (string) $sanitized));
    }
}
