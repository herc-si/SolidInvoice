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

namespace Augias\TaxBundle\Tests\Entity;

use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\InvoiceBundle\Entity\Line as InvoiceLine;
use Augias\InvoiceBundle\Test\Factory\InvoiceFactory;
use Augias\QuoteBundle\Entity\Line as QuoteLine;
use Augias\QuoteBundle\Test\Factory\QuoteFactory;
use Augias\TaxBundle\Entity\LineTax;
use Augias\TaxBundle\Enum\TaxCategory;
use Augias\TaxBundle\Enum\TaxType;
use Augias\TaxBundle\Validator\Constraints\ExactlyOneLine;
use Augias\TaxBundle\Validator\Constraints\ExactlyOneLineValidator;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(LineTax::class)]
#[CoversClass(ExactlyOneLine::class)]
#[CoversClass(ExactlyOneLineValidator::class)]
final class LineTaxTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private ValidatorInterface $validator;

    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = self::getContainer()->get('validator');
        $this->registry = self::getContainer()->get('doctrine');
    }

    public function testValidatorRejectsLineTaxWithNeitherLineSet(): void
    {
        $lineTax = $this->buildLineTax();

        $violations = $this->validator->validate($lineTax);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame((new ExactlyOneLine())->message, $violations->get(0)->getMessage());
    }

    public function testValidatorRejectsLineTaxWithBothLinesSet(): void
    {
        $invoiceLine = new InvoiceLine();
        $quoteLine = new QuoteLine();

        $lineTax = $this->buildLineTax();
        $lineTax->setInvoiceLine($invoiceLine);
        $lineTax->setQuoteLine($quoteLine);

        $violations = $this->validator->validate($lineTax);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame((new ExactlyOneLine())->message, $violations->get(0)->getMessage());
    }

    public function testValidatorAcceptsLineTaxWithOnlyInvoiceLineSet(): void
    {
        $invoiceLine = new InvoiceLine();

        $lineTax = $this->buildLineTax();
        $lineTax->setInvoiceLine($invoiceLine);

        self::assertCount(0, $this->validator->validate($lineTax));
    }

    public function testValidatorAcceptsLineTaxWithOnlyQuoteLineSet(): void
    {
        $quoteLine = new QuoteLine();

        $lineTax = $this->buildLineTax();
        $lineTax->setQuoteLine($quoteLine);

        self::assertCount(0, $this->validator->validate($lineTax));
    }

    public function testCompanyIsInheritedFromParentInvoiceLineOnPersist(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $invoice = InvoiceFactory::createOne(['company' => $this->company, 'client' => $client]);

        $line = new InvoiceLine();
        $line->setDescription('Sample');
        $line->setPrice(1000);
        $line->setQty(1);
        $line->setInvoice($invoice);

        $lineTax = $this->buildLineTax();
        $line->addTax($lineTax);

        $em = $this->registry->getManagerForClass(LineTax::class);
        self::assertInstanceOf(ObjectManager::class, $em);
        $em->persist($line);
        $em->flush();

        self::assertSame(
            $this->company->getId()
                ->toRfc4122(),
            $lineTax->getCompany()
                ->getId()
                ->toRfc4122(),
        );
    }

    public function testCompanyIsInheritedFromParentQuoteLineOnPersist(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $quote = QuoteFactory::createOne(['company' => $this->company, 'client' => $client]);

        $line = new QuoteLine();
        $line->setDescription('Sample');
        $line->setPrice(1000);
        $line->setQty(1);
        $line->setQuote($quote);

        $lineTax = $this->buildLineTax();
        $line->addTax($lineTax);

        $em = $this->registry->getManagerForClass(LineTax::class);
        self::assertInstanceOf(ObjectManager::class, $em);
        $em->persist($line);
        $em->flush();

        self::assertSame(
            $this->company->getId()
                ->toRfc4122(),
            $lineTax->getCompany()
                ->getId()
                ->toRfc4122(),
        );
    }

    private function buildLineTax(): LineTax
    {
        $lineTax = new LineTax();
        $lineTax->setNameSnapshot('VAT');
        $lineTax->setRateSnapshot('15.0000');
        $lineTax->setCategorySnapshot(TaxCategory::Standard);
        $lineTax->setTypeSnapshot(TaxType::Exclusive);

        return $lineTax;
    }
}
