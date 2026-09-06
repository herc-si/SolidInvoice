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

namespace Augias\BillBundle\Tests\Entity;

use Augias\BillBundle\Entity\Bill;
use Augias\BillBundle\Entity\BillCategory;
use Augias\BillBundle\Entity\BillPayment;
use Augias\BillBundle\Enum\BillPaymentMethod;
use Augias\BillBundle\Enum\BillStatus;
use Augias\ClientBundle\Entity\Client;
use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceReceipt;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Bill::class)]
final class BillTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $supplier = new Client();
        $category = new BillCategory();
        $receipt = new ElectronicInvoiceReceipt();
        $issueDate = new DateTimeImmutable('2026-01-01');
        $dueDate = new DateTimeImmutable('2026-01-31');

        $bill = new Bill();
        $bill->setSupplier($supplier)
            ->setBillNumber('SUP-0042')
            ->setStatus(BillStatus::Pending)
            ->setIssueDate($issueDate)
            ->setDueDate($dueDate)
            ->setTotalAmount(BigInteger::of(10000))
            ->setCurrencyCode('EUR')
            ->setCategory($category)
            ->setNotes('Office supplies')
            ->setElectronicInvoiceReceipt($receipt)
            ->setDocumentPath('var/bills/company/1.pdf')
            ->setDocumentMimeType('application/pdf');

        self::assertSame($supplier, $bill->getSupplier());
        self::assertSame('SUP-0042', $bill->getBillNumber());
        self::assertSame(BillStatus::Pending, $bill->getStatus());
        self::assertSame($issueDate, $bill->getIssueDate());
        self::assertSame($dueDate, $bill->getDueDate());
        self::assertSame('10000', (string) $bill->getTotalAmount());
        self::assertSame('EUR', $bill->getCurrencyCode());
        self::assertSame($category, $bill->getCategory());
        self::assertSame('Office supplies', $bill->getNotes());
        self::assertSame($receipt, $bill->getElectronicInvoiceReceipt());
        self::assertSame('var/bills/company/1.pdf', $bill->getDocumentPath());
        self::assertSame('application/pdf', $bill->getDocumentMimeType());
        self::assertTrue($bill->hasDocument());
    }

    public function testStatusValueMirrorsStatus(): void
    {
        $bill = new Bill();

        self::assertSame(BillStatus::Draft->value, $bill->getStatusValue());

        $bill->setStatusValue(BillStatus::Paid->value);

        self::assertSame(BillStatus::Paid, $bill->getStatus());
    }

    public function testGetTotalCombinesTotalAmountAndCurrencyCode(): void
    {
        $bill = new Bill();
        $bill->setTotalAmount(BigInteger::of(10000))
            ->setCurrencyCode('EUR');

        $total = $bill->getTotal();

        self::assertSame('10000', $total->getAmount());
        self::assertSame('EUR', $total->getCurrency()->getCode());
    }

    public function testGetPaidAmountSumsAllPayments(): void
    {
        $bill = new Bill();
        $bill->setTotalAmount(BigInteger::of(10000))->setCurrencyCode('EUR');

        $bill->addPayment($this->payment(4000));
        $bill->addPayment($this->payment(1000));

        self::assertSame('5000', (string) $bill->getPaidAmount());
    }

    public function testGetBalanceIsTotalMinusPaidAmount(): void
    {
        $bill = new Bill();
        $bill->setTotalAmount(BigInteger::of(10000))->setCurrencyCode('EUR');
        $bill->addPayment($this->payment(4000));

        $balance = $bill->getBalance();

        self::assertSame('6000', $balance->getAmount());
    }

    public function testGetBalanceIsFlooredAtZeroWhenOverpaid(): void
    {
        $bill = new Bill();
        $bill->setTotalAmount(BigInteger::of(10000))->setCurrencyCode('EUR');
        $bill->addPayment($this->payment(15000));

        $balance = $bill->getBalance();

        self::assertTrue($balance->isZero());
    }

    public function testAddPaymentIsIdempotentAndSetsInverseSide(): void
    {
        $bill = new Bill();
        $payment = $this->payment(1000);

        $bill->addPayment($payment);
        $bill->addPayment($payment);

        self::assertCount(1, $bill->getPayments());
        self::assertSame($bill, $payment->getBill());
    }

    public function testHasDocumentFallsBackToTheReceiptsDocument(): void
    {
        $receipt = new ElectronicInvoiceReceipt();
        $receipt->setDocumentPath('var/einvoicing/incoming/company/1.pdf');

        $bill = new Bill();
        $bill->setElectronicInvoiceReceipt($receipt);

        self::assertTrue($bill->hasDocument());
    }

    public function testHasDocumentIsFalseWithNeitherADocumentNorAReceipt(): void
    {
        $bill = new Bill();

        self::assertFalse($bill->hasDocument());
    }

    private function payment(int $amount): BillPayment
    {
        $payment = new BillPayment();
        $payment->setAmount(BigInteger::of($amount))
            ->setCurrencyCode('EUR')
            ->setPaidDate(new DateTimeImmutable('2026-01-15'))
            ->setMethod(BillPaymentMethod::BankTransfer);

        return $payment;
    }
}
