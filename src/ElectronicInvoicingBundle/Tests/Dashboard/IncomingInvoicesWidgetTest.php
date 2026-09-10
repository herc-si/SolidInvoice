<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\ElectronicInvoicingBundle\Tests\Dashboard;

use Augias\BillBundle\Entity\Bill;
use Augias\BillBundle\Enum\BillStatus;
use Augias\ClientBundle\Entity\Client;
use Augias\ElectronicInvoicingBundle\Dashboard\IncomingInvoicesWidget;
use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceReceipt;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(IncomingInvoicesWidget::class)]
final class IncomingInvoicesWidgetTest extends EInvoicingWidgetTestCase
{
    /**
     * A company that has not switched electronic invoicing on has no inbox, and
     * a card that would only ever be empty has no business in the picker.
     */
    public function testDoesNotApplyWithoutAnActiveProvider(): void
    {
        self::assertFalse($this->widget()->supports());
    }

    public function testAppliesOnceAProviderIsActive(): void
    {
        $this->activateTestProvider();

        self::assertTrue($this->widget()->supports());
    }

    public function testAnEmptyInboxIsNotAnError(): void
    {
        $this->activateTestProvider();

        $data = $this->widget()->getData();

        self::assertSame([], $data['receipts']);
        self::assertSame(0, $data['awaitingTotal']);
    }

    public function testListsWhatHasArrivedAndNotBeenDealtWith(): void
    {
        $this->activateTestProvider();
        $this->receipt('DEMO-0001');

        $data = $this->widget()->getData();

        self::assertCount(1, $data['receipts']);
        self::assertSame(1, $data['awaitingTotal']);
    }

    /**
     * "Dealt with" is a Bill pointing at the receipt — there is no flag on the
     * receipt itself — so creating the purchase invoice is what empties the
     * inbox. The list and the count have to agree about that.
     */
    public function testAReceiptTurnedIntoABillLeavesTheInbox(): void
    {
        $this->activateTestProvider();
        $receipt = $this->receipt('DEMO-0001');
        $this->billFrom($receipt);

        $data = $this->widget()->getData();

        self::assertSame([], $data['receipts']);
        self::assertSame(0, $data['awaitingTotal']);
    }

    public function testTheNewestArrivalComesFirst(): void
    {
        $this->activateTestProvider();
        $this->receipt('OLD-0001');
        $this->receipt('NEW-0002');

        $receipts = $this->widget()->getData()['receipts'];

        self::assertSame('NEW-0002', $receipts[0]->getInvoiceNumber());
    }

    public function testGetTemplate(): void
    {
        self::assertSame(
            '@AugiasElectronicInvoicing/Widget/incoming.html.twig',
            $this->widget()->getTemplate(),
        );
    }

    public function testRendersAnEmptyInbox(): void
    {
        $this->activateTestProvider();

        $this->assertMatchesHtmlSnapshot($this->render($this->widget()));
    }

    public function testRendersWhatIsWaiting(): void
    {
        $this->activateTestProvider();
        $this->receipt('DEMO-0001');

        $this->assertMatchesHtmlSnapshot($this->render($this->widget()));
    }

    private function receipt(string $invoiceNumber): ElectronicInvoiceReceipt
    {
        $receipt = new ElectronicInvoiceReceipt();
        $receipt->setCompany($this->company)
            ->setProvider('test_provider')
            ->setExternalReference('ext-' . $invoiceNumber)
            ->setInvoiceNumber($invoiceNumber)
            ->setSellerName('Demo Supplier Inc.')
            ->setIssueDate(new DateTimeImmutable('2026-02-14'))
            ->setTotalAmount(BigInteger::of(12_000))
            ->setCurrencyCode('EUR');

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        return $receipt;
    }

    private function billFrom(ElectronicInvoiceReceipt $receipt): void
    {
        $supplier = new Client();
        $supplier->setCompany($this->company)
            ->setName('Demo Supplier Inc.')
            ->setIsClient(false)
            ->setIsSupplier(true);

        $this->entityManager->persist($supplier);

        $bill = new Bill();
        $bill->setCompany($this->company)
            ->setSupplier($supplier)
            ->setStatus(BillStatus::Pending)
            ->setTotalAmount(BigInteger::of(12_000))
            ->setCurrencyCode('EUR')
            ->setElectronicInvoiceReceipt($receipt);

        $this->entityManager->persist($bill);
        $this->entityManager->flush();
    }

    private function widget(): IncomingInvoicesWidget
    {
        $widget = self::getContainer()->get(IncomingInvoicesWidget::class);
        self::assertInstanceOf(IncomingInvoicesWidget::class, $widget);

        return $widget;
    }
}
