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

namespace SolidInvoice\ElectronicInvoicingBundle\Tests\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdpProvider;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Test\Factory\TaxIdentifierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function json_encode;

#[CoversClass(SuperPdpProvider::class)]
final class SuperPdpProviderTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testSendUploadsTheInvoiceAndReturnsTheSuperPdpId(): void
    {
        self::getContainer()->set(HttpClientInterface::class, new MockHttpClient([
            static fn (): MockResponse => new MockResponse((string) json_encode(['access_token' => 'a-token', 'expires_in' => 3600])),
            static fn (): MockResponse => new MockResponse((string) json_encode(['id' => 4242, 'events' => []])),
        ]));

        $invoice = $this->createEligibleInvoice();

        $result = self::getContainer()->get(SuperPdpProvider::class)->send($invoice, [
            'client_id' => 'id',
            'client_secret' => 'secret',
        ]);

        self::assertTrue($result->success);
        self::assertSame('4242', $result->externalReference);
    }

    public function testSendReturnsAFailureWhenCredentialsAreMissing(): void
    {
        $invoice = $this->createEligibleInvoice();

        $result = self::getContainer()->get(SuperPdpProvider::class)->send($invoice, []);

        self::assertFalse($result->success);
        self::assertSame('einvoicing.provider.super_pdp.missing_credentials', $result->message);
    }

    public function testSendReturnsAFailureWhenTheApiRejectsTheDocument(): void
    {
        self::getContainer()->set(HttpClientInterface::class, new MockHttpClient([
            static fn (): MockResponse => new MockResponse((string) json_encode(['access_token' => 'a-token', 'expires_in' => 3600])),
            static fn (): MockResponse => new MockResponse(
                (string) json_encode(['code' => 1, 'http_status_code' => 400, 'message' => 'Invalid document']),
                ['http_code' => 400],
            ),
        ]));

        $invoice = $this->createEligibleInvoice();

        $result = self::getContainer()->get(SuperPdpProvider::class)->send($invoice, [
            'client_id' => 'id',
            'client_secret' => 'secret',
        ]);

        self::assertFalse($result->success);
        self::assertStringContainsString('Invalid document', (string) $result->message);
    }

    private function createEligibleInvoice(): Invoice
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'EUR']);

        TaxIdentifierFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'label' => 'SIRET',
            'value' => '22222222200022',
        ]);

        $invoice = new Invoice();
        $invoice->setCompany($this->company);
        $invoice->setClient($client);
        $invoice->setInvoiceId('INV-0001');
        $invoice->setStatus(InvoiceStatus::Draft);

        $line = new Line();
        $line->setDescription('Consulting services');
        $line->setPrice(10000);
        $line->setQty(1);
        $line->updateTotal();

        $lineTax = new LineTax();
        $lineTax->setNameSnapshot('VAT');
        $lineTax->setRateSnapshot('20.0000');
        $line->addTax($lineTax);

        $invoice->addLine($line);

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($invoice);
        $entityManager->flush();

        return $invoice;
    }
}
