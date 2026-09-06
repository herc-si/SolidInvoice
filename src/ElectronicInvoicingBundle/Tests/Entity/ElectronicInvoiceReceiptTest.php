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

namespace Augias\ElectronicInvoicingBundle\Tests\Entity;

use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceReceipt;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ElectronicInvoiceReceipt::class)]
final class ElectronicInvoiceReceiptTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $issueDate = new DateTimeImmutable('2026-01-01');

        $receipt = new ElectronicInvoiceReceipt();
        $receipt->setProvider('super_pdp')
            ->setExternalReference('555')
            ->setInvoiceNumber('SUP-0042')
            ->setSellerName('Acme Supplies')
            ->setSellerIdentifier('111222333')
            ->setIssueDate($issueDate)
            ->setTotalAmount(BigInteger::of(19990))
            ->setCurrencyCode('EUR')
            ->setStatusCode('fr:205')
            ->setDocumentPath('var/einvoicing/incoming/company/555.pdf')
            ->setDocumentMimeType('application/pdf');

        self::assertSame('super_pdp', $receipt->getProvider());
        self::assertSame('555', $receipt->getExternalReference());
        self::assertSame('SUP-0042', $receipt->getInvoiceNumber());
        self::assertSame('Acme Supplies', $receipt->getSellerName());
        self::assertSame('111222333', $receipt->getSellerIdentifier());
        self::assertSame($issueDate, $receipt->getIssueDate());
        self::assertSame('19990', (string) $receipt->getTotalAmount());
        self::assertSame('EUR', $receipt->getCurrencyCode());
        self::assertSame('fr:205', $receipt->getStatusCode());
        self::assertSame('var/einvoicing/incoming/company/555.pdf', $receipt->getDocumentPath());
        self::assertSame('application/pdf', $receipt->getDocumentMimeType());
        self::assertTrue($receipt->hasDocument());
    }

    public function testHasDocumentIsFalseUntilADocumentPathIsSet(): void
    {
        $receipt = new ElectronicInvoiceReceipt();

        self::assertFalse($receipt->hasDocument());
    }

    public function testGetAmountCombinesTotalAmountAndCurrencyCode(): void
    {
        $receipt = new ElectronicInvoiceReceipt();
        $receipt->setTotalAmount(BigInteger::of(19990))
            ->setCurrencyCode('EUR');

        $amount = $receipt->getAmount();

        self::assertNotNull($amount);
        self::assertSame('19990', $amount->getAmount());
        self::assertSame('EUR', $amount->getCurrency()->getCode());
    }

    public function testGetAmountIsNullWithoutATotalAmountOrCurrency(): void
    {
        $receipt = new ElectronicInvoiceReceipt();

        self::assertNull($receipt->getAmount());

        $receipt->setTotalAmount(BigInteger::of(100));
        self::assertNull($receipt->getAmount());
    }
}
