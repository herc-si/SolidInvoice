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

use Psr\Log\LoggerInterface;
use SolidInvoice\ElectronicInvoicingBundle\Form\Type\Provider\SuperPdpConfigType;
use SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdp\FacturXInvoiceBuilder;
use SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdp\SuperPdpApiException;
use SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdp\SuperPdpClient;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Throwable;
use function is_int;
use function is_string;

/**
 * Sends the invoice as a Factur-X document to SUPER PDP
 * (https://www.superpdp.tech), a French Plateforme Agréée (PA/PDP) for the
 * electronic-invoicing reform, and, more generally, the Peppol network.
 *
 * @see \SolidInvoice\ElectronicInvoicingBundle\Tests\Provider\SuperPdpProviderTest
 */
#[AsTaggedItem('super_pdp')]
final readonly class SuperPdpProvider implements ElectronicInvoiceProviderInterface
{
    public function __construct(
        private FacturXInvoiceBuilder $documentBuilder,
        private SuperPdpClient $client,
        private LoggerInterface $logger,
    ) {
    }

    public static function getName(): string
    {
        return 'super_pdp';
    }

    public function getForm(): string
    {
        return SuperPdpConfigType::class;
    }

    /**
     * @param array{client_id?: mixed, client_secret?: mixed} $config
     */
    public function send(Invoice $invoice, array $config): ElectronicInvoiceSubmissionResult
    {
        $clientId = $config['client_id'] ?? null;
        $clientSecret = $config['client_secret'] ?? null;

        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
            return ElectronicInvoiceSubmissionResult::failure('einvoicing.provider.super_pdp.missing_credentials');
        }

        try {
            $document = $this->documentBuilder->build($invoice);
            $accessToken = $this->client->getAccessToken($clientId, $clientSecret);
            $response = $this->client->sendInvoice($accessToken, $document, (string) $invoice->getId());
        } catch (SuperPdpApiException $e) {
            $this->logger->error('SUPER PDP rejected the invoice submission.', ['exception' => $e, 'invoice' => (string) $invoice->getId()]);

            return ElectronicInvoiceSubmissionResult::failure($e->getMessage());
        } catch (Throwable $e) {
            $this->logger->error('Failed to build or send the Factur-X document for SUPER PDP.', ['exception' => $e, 'invoice' => (string) $invoice->getId()]);

            return ElectronicInvoiceSubmissionResult::failure('einvoicing.provider.super_pdp.build_failed');
        }

        $externalReference = $response['id'] ?? null;

        return ElectronicInvoiceSubmissionResult::success(
            is_int($externalReference) || is_string($externalReference) ? (string) $externalReference : null,
        );
    }
}
