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

namespace SolidInvoice\ElectronicInvoicingBundle\Tests\Provider\SuperPdp;

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdp\FacturXInvoiceBuilder;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Test\Factory\TaxIdentifierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function json_encode;

#[CoversClass(FacturXInvoiceBuilder::class)]
final class FacturXInvoiceBuilderTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testBuildDocumentProducesAnEn16931XmlWithSellerBuyerAndLines(): void
    {
        self::getContainer()->get(SystemConfig::class)->set('system/company/company_name', 'Acme Corp');
        self::getContainer()->get(SystemConfig::class)->set('system/company/contact_details/address', (string) json_encode([
            'street1' => '1 Rue de la Paix',
            'street2' => null,
            'city' => 'Paris',
            'state' => null,
            'zip' => '75002',
            'country' => 'FR',
        ]));

        TaxIdentifierFactory::createOne([
            'company' => $this->company,
            'client' => null,
            'label' => 'SIRET',
            'value' => '11111111100011',
        ]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'EUR']);

        TaxIdentifierFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'label' => 'SIRET',
            'value' => '22222222200022',
        ]);

        $address = new Address();
        $address->setStreet1('10 Rue du Client')->setCity('Lyon')->setZip('69001')->setCountry('FR');
        $client->addAddress($address);

        $invoice = new Invoice();
        $invoice->setCompany($this->company);
        $invoice->setClient($client);
        $invoice->setInvoiceId('INV-0001');
        $invoice->setStatus(InvoiceStatus::Draft);

        $line = new Line();
        $line->setDescription('Consulting services');
        $line->setPrice(10000);
        $line->setQty(2);
        $line->updateTotal();

        $lineTax = new LineTax();
        $lineTax->setNameSnapshot('VAT');
        $lineTax->setRateSnapshot('20.0000');
        $line->addTax($lineTax);

        $invoice->addLine($line);

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($invoice);
        $entityManager->flush();

        $documentBuilder = self::getContainer()->get(FacturXInvoiceBuilder::class)->buildDocument($invoice);
        $xml = $documentBuilder->getContent();

        self::assertStringContainsString('INV-0001', $xml);
        self::assertStringContainsString('Acme Corp', $xml);
        self::assertStringContainsString('11111111100011', $xml);
        self::assertStringContainsString('22222222200022', $xml);
        self::assertStringContainsString('Consulting services', $xml);
        self::assertStringContainsString('EUR', $xml);

        // The SIRET must be a scheme-qualified GlobalID (ISO 6523 ICD "0002", the
        // French SIRENE registry) — not a plain tax-registration number — otherwise
        // SUPER PDP cannot compute the processing_rule (B2B/B2C/B2G) from the document.
        self::assertMatchesRegularExpression(
            '/<ram:GlobalID schemeID="0002">11111111100011<\/ram:GlobalID>/',
            $xml,
        );
        self::assertMatchesRegularExpression(
            '/<ram:GlobalID schemeID="0002">22222222200022<\/ram:GlobalID>/',
            $xml,
        );

        // BT-34 Seller electronic address: AFNOR/Peppol routing for France uses the
        // SIREN/SIRET, not an email — providers like SUPER PDP report it "missing"
        // otherwise.
        self::assertMatchesRegularExpression(
            '/<ram:URIID schemeID="0002">11111111100011<\/ram:URIID>/',
            $xml,
        );

        // BT-30 Seller legal registration identifier: a separate field from GlobalID
        // and the electronic address — SUPER PDP reports it missing independently.
        self::assertMatchesRegularExpression(
            '/<ram:SpecifiedLegalOrganization>\s*<ram:ID schemeID="0002">11111111100011<\/ram:ID>/',
            $xml,
        );

        // BT-23 mandatory "cadre de facturation" code (BR-FR-08).
        self::assertStringContainsString('<ram:ID>S1</ram:ID>', $xml);

        // BR-FR-05 mandatory French legal mentions (recovery fees, late-payment
        // penalties, early-payment discount policy).
        self::assertStringContainsString('<ram:SubjectCode>PMT</ram:SubjectCode>', $xml);
        self::assertStringContainsString('<ram:SubjectCode>PMD</ram:SubjectCode>', $xml);
        self::assertStringContainsString('<ram:SubjectCode>AAB</ram:SubjectCode>', $xml);

        // A delivery/supply date, so ApplicableHeaderTradeDelivery isn't left empty
        // (PEPPOL-EN16931-R008).
        self::assertStringContainsString('ActualDeliverySupplyChainEvent', $xml);

        // Seller and buyer postal addresses, each with a country code (BR-08/09/10/11).
        self::assertMatchesRegularExpression('/<ram:PostalTradeAddress>.*?<ram:CountryID>FR<\/ram:CountryID>/s', $xml);

        // Amounts are minor units (cents) on Line/Invoice — price 10000 (=100.00 EUR)
        // x qty 2 must come out as 200.00 EUR, not 20000.00 (100x too large).
        self::assertStringContainsString('<ram:ChargeAmount>100.00</ram:ChargeAmount>', $xml);
        self::assertStringContainsString('<ram:LineTotalAmount>200.00</ram:LineTotalAmount>', $xml);
        self::assertStringContainsString('<ram:TaxBasisTotalAmount>200.00</ram:TaxBasisTotalAmount>', $xml);
    }

    public function testBuildProducesAFacturXPdfWithTheXmlEmbedded(): void
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
        $invoice->setInvoiceId('INV-0002');
        $invoice->setStatus(InvoiceStatus::Draft);

        $line = new Line();
        $line->setDescription('Consulting services');
        $line->setPrice(10000);
        $line->setQty(1);
        $line->updateTotal();
        $invoice->addLine($line);

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($invoice);
        $entityManager->flush();

        $pdf = self::getContainer()->get(FacturXInvoiceBuilder::class)->build($invoice);

        self::assertStringStartsWith('%PDF-', $pdf);
    }
}
