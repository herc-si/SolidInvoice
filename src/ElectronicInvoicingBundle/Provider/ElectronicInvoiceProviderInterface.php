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

use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Implemented by each electronic-invoicing platform (Chorus Pro, a PDP, ...).
 *
 * A provider owns both generating its own document format (Factur-X, UBL,
 * CII, ...) and transmitting it — there is deliberately no shared central
 * generator, so adding a new platform is just a new class implementing this
 * interface, tagged for auto-discovery like SolidInvoice\NotificationBundle's
 * ConfiguratorInterface.
 */
#[AutoconfigureTag(self::DI_TAG)]
interface ElectronicInvoiceProviderInterface
{
    public const string DI_TAG = 'einvoicing.provider';

    /**
     * Unique machine key for this provider, e.g. "chorus_pro". Stored on
     * ElectronicInvoiceProviderSetting::$provider to resolve back to the
     * tagged service.
     */
    public static function getName(): string;

    /**
     * FQCN of the Symfony Form Type used to configure this provider's
     * settings (API credentials, endpoint, ...).
     */
    public function getForm(): string;

    /**
     * Generate and transmit the electronic invoice for $invoice using this
     * provider's own $config (ElectronicInvoiceProviderSetting::$settings).
     *
     * @param array<string, mixed> $config
     */
    public function send(Invoice $invoice, array $config): ElectronicInvoiceSubmissionResult;
}
