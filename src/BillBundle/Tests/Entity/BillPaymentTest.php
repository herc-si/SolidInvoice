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

namespace SolidInvoice\BillBundle\Tests\Entity;

use Brick\Math\BigInteger;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\BillBundle\Entity\Bill;
use SolidInvoice\BillBundle\Entity\BillPayment;
use SolidInvoice\BillBundle\Enum\BillPaymentMethod;

#[CoversClass(BillPayment::class)]
final class BillPaymentTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $bill = new Bill();
        $paidDate = new DateTimeImmutable('2026-01-15');

        $payment = new BillPayment();
        $payment->setBill($bill)
            ->setAmount(BigInteger::of(4000))
            ->setCurrencyCode('EUR')
            ->setPaidDate($paidDate)
            ->setMethod(BillPaymentMethod::BankTransfer)
            ->setReference('VIR-001')
            ->setNotes('Partial payment');

        self::assertSame($bill, $payment->getBill());
        self::assertSame('4000', (string) $payment->getAmount());
        self::assertSame('EUR', $payment->getCurrencyCode());
        self::assertSame($paidDate, $payment->getPaidDate());
        self::assertSame(BillPaymentMethod::BankTransfer, $payment->getMethod());
        self::assertSame('VIR-001', $payment->getReference());
        self::assertSame('Partial payment', $payment->getNotes());
    }

    public function testGetMoneyCombinesAmountAndCurrencyCode(): void
    {
        $payment = new BillPayment();
        $payment->setAmount(BigInteger::of(4000))->setCurrencyCode('EUR');

        $money = $payment->getMoney();

        self::assertSame('4000', $money->getAmount());
        self::assertSame('EUR', $money->getCurrency()->getCode());
    }
}
