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

use LogicException;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;
use SolidInvoice\ElectronicInvoicingBundle\Repository\ElectronicInvoiceProviderSettingRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Resolves the company's active provider setting and dispatches to the
 * matching tagged ElectronicInvoiceProviderInterface — the equivalent of
 * NotificationBundle's NotificationTransportFactory for this bundle.
 */
final readonly class ElectronicInvoiceProviderRegistry
{
    /**
     * @param ServiceLocator<ElectronicInvoiceProviderInterface> $providers
     */
    public function __construct(
        #[AutowireLocator(ElectronicInvoiceProviderInterface::DI_TAG)]
        private ServiceLocator $providers,
        private ElectronicInvoiceProviderSettingRepository $settingRepository,
    ) {
    }

    public function hasActiveProvider(): bool
    {
        return $this->settingRepository->findActive() instanceof ElectronicInvoiceProviderSetting;
    }

    public function send(Invoice $invoice): ElectronicInvoiceSubmissionResult
    {
        $setting = $this->settingRepository->findActive();

        if (! $setting instanceof ElectronicInvoiceProviderSetting) {
            throw new LogicException('No active electronic invoicing provider is configured.');
        }

        if (! $this->providers->has($setting->getProvider())) {
            throw new LogicException(sprintf('Unknown electronic invoicing provider "%s".', $setting->getProvider()));
        }

        /** @var ElectronicInvoiceProviderInterface $provider */
        $provider = $this->providers->get($setting->getProvider());

        return $provider->send($invoice, $setting->getSettings());
    }
}
