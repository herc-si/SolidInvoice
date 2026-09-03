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

use SolidInvoice\ElectronicInvoicingBundle\Form\Type\Provider\TestProviderConfigType;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Uid\Uuid;

/**
 * Simulates a successful (or, on demand, failed) submission without ever
 * calling out to a real platform. Exists to prove the provider pipeline
 * (settings, marketplace UI, dispatch, submission history) end-to-end
 * before a real platform (Chorus Pro, a PDP, ...) is implemented.
 */
#[AsTaggedItem('test_provider')]
final class TestProvider implements ElectronicInvoiceProviderInterface
{
    public static function getName(): string
    {
        return 'test_provider';
    }

    public function getForm(): string
    {
        return TestProviderConfigType::class;
    }

    /**
     * @param array{reference_prefix?: string, simulate_failure?: bool} $config
     */
    public function send(Invoice $invoice, array $config): ElectronicInvoiceSubmissionResult
    {
        if ($config['simulate_failure'] ?? false) {
            return ElectronicInvoiceSubmissionResult::failure('einvoicing.provider.test.simulated_failure');
        }

        $prefix = $config['reference_prefix'] ?? 'TEST';

        return ElectronicInvoiceSubmissionResult::success(
            sprintf('%s-%s', $prefix, Uuid::v4()),
        );
    }
}
