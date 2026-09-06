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
use function substr_count;

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
        self::assertStringContainsString('Consulting services', $xml);
        self::assertStringContainsString('EUR', $xml);

        // The SIRET must be a scheme-qualified GlobalID (ISO 6523 ICD "0002", the
        // French SIRENE registry) — not a plain tax-registration number — otherwise
        // SUPER PDP cannot compute the processing_rule (B2B/B2C/B2G) from the document.
        // BR-FR-32 requires exactly 9 digits under this scheme, so it's the SIREN
        // (the 14-digit SIRET's leading 9 digits), not the full SIRET.
        self::assertMatchesRegularExpression(
            '/<ram:GlobalID schemeID="0002">111111111<\/ram:GlobalID>/',
            $xml,
        );
        self::assertMatchesRegularExpression(
            '/<ram:GlobalID schemeID="0002">222222222<\/ram:GlobalID>/',
            $xml,
        );

        // BT-34 Seller electronic address: this is the actual Peppol routing/delivery
        // address, not just an identity field — SUPER PDP's directory only resolves
        // French recipients under scheme "0225", keyed on the 9-digit SIREN (the
        // SIRET's first 9 digits), never "0002" (SIRENE is a valid EN16931 identity
        // scheme but isn't a routable address on their network).
        self::assertMatchesRegularExpression(
            '/<ram:URIID schemeID="0225">111111111<\/ram:URIID>/',
            $xml,
        );
        self::assertMatchesRegularExpression(
            '/<ram:URIID schemeID="0225">222222222<\/ram:URIID>/',
            $xml,
        );

        // BT-30 Seller legal registration identifier: a separate field from GlobalID
        // and the electronic address — SUPER PDP reports it missing independently.
        self::assertMatchesRegularExpression(
            '/<ram:SpecifiedLegalOrganization>\s*<ram:ID schemeID="0002">111111111<\/ram:ID>/',
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

    /**
     * SUPER PDP's own sandbox test addresses (e.g. "315143296_92569") aren't
     * 14-digit SIRETs — buildDocument() must pass a value like that through to
     * the routing address unchanged rather than truncating it to 9 characters,
     * since only an unambiguous 14-digit numeric SIRET should be shortened to
     * its SIREN.
     */
    public function testBuyerElectronicAddressIsNotTruncatedWhenTheIdentifierIsNotAFourteenDigitSiret(): void
    {
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
            'value' => '315143296_92569',
        ]);

        $invoice = new Invoice();
        $invoice->setCompany($this->company);
        $invoice->setClient($client);
        $invoice->setInvoiceId('INV-0003');
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

        $xml = self::getContainer()->get(FacturXInvoiceBuilder::class)->buildDocument($invoice)->getContent();

        // BT-49 electronic address: the full value, untouched.
        self::assertMatchesRegularExpression(
            '/<ram:URIID schemeID="0225">315143296_92569<\/ram:URIID>/',
            $xml,
        );

        // BT-46/BT-47 GlobalID/LegalOrganisation: BR-FR-32 requires exactly 9
        // digits under scheme 0002 — derived here from the identifier's
        // all-numeric leading 9 characters, not the full compound value.
        self::assertMatchesRegularExpression(
            '/<ram:GlobalID schemeID="0002">315143296<\/ram:GlobalID>/',
            $xml,
        );
        self::assertMatchesRegularExpression(
            '/<ram:SpecifiedLegalOrganization>\s*<ram:ID schemeID="0002">315143296<\/ram:ID>/',
            $xml,
        );
    }

    /**
     * BR-FR-32 (scheme 0002 identifiers must be exactly 9 digits) can't be
     * satisfied by an identifier with no 9-digit numeric prefix at all — the
     * field must be omitted rather than emit a value that would fail schema
     * validation, even though the routing address (BT-49, which has no such
     * digit-count rule) still gets the raw value.
     */
    public function testGlobalIdIsOmittedWhenNoCompliantSirenCanBeDerived(): void
    {
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
            'value' => 'not-a-siren-at-all',
        ]);

        $invoice = new Invoice();
        $invoice->setCompany($this->company);
        $invoice->setClient($client);
        $invoice->setInvoiceId('INV-0004');
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

        $xml = self::getContainer()->get(FacturXInvoiceBuilder::class)->buildDocument($invoice)->getContent();

        // BT-49 electronic address still gets the raw value — no digit-count rule applies here.
        self::assertMatchesRegularExpression(
            '/<ram:URIID schemeID="0225">not-a-siren-at-all<\/ram:URIID>/',
            $xml,
        );

        // The value appears exactly once — as that electronic address — never as a
        // scheme-0002 GlobalID or SpecifiedLegalOrganization identifier.
        self::assertSame(1, substr_count($xml, 'not-a-siren-at-all'));
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
