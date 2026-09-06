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

namespace Augias\TaxBundle\Tests\Listener;

use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Entity\Line as InvoiceLine;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\QuoteBundle\Entity\Line as QuoteLine;
use Augias\QuoteBundle\Entity\Quote;
use Augias\QuoteBundle\Enum\QuoteStatus;
use Augias\TaxBundle\Entity\InvoiceTax;
use Augias\TaxBundle\Entity\LineTax;
use Augias\TaxBundle\Entity\Tax;
use Augias\TaxBundle\Listener\SnapshotTaxesOnIssueListener;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

#[AllowMockObjectsWithoutExpectations]
final class SnapshotTaxesOnIssueListenerTest extends TestCase
{
    public function testInvoiceDraftToPendingTransitionStampsAllUnsetSnapshots(): void
    {
        $invoice = $this->buildInvoiceWithTaxedLine();
        $listener = new SnapshotTaxesOnIssueListener();

        $event = $this->makeEvent(
            $invoice,
            'invoice',
            new Transition('accept', InvoiceStatus::Draft->value, InvoiceStatus::Pending->value),
        );

        $listener->onTransition($event);

        $lineTax = $invoice->getLines()->first()->getTaxes()->first();
        self::assertInstanceOf(DateTimeImmutable::class, $lineTax->getSnapshottedAt());
    }

    public function testQuoteDraftToPendingTransitionStampsAllUnsetSnapshots(): void
    {
        $quote = $this->buildQuoteWithTaxedLine();
        $listener = new SnapshotTaxesOnIssueListener();

        $event = $this->makeEvent(
            $quote,
            'quote',
            new Transition('send', QuoteStatus::Draft->value, QuoteStatus::Pending->value),
        );

        $listener->onTransition($event);

        $lineTax = $quote->getLines()->first()->getTaxes()->first();
        self::assertInstanceOf(DateTimeImmutable::class, $lineTax->getSnapshottedAt());
    }

    public function testCancelFromDraftDoesNotStampSnapshots(): void
    {
        $invoice = $this->buildInvoiceWithTaxedLine();
        $listener = new SnapshotTaxesOnIssueListener();

        $event = $this->makeEvent(
            $invoice,
            'invoice',
            new Transition('cancel', InvoiceStatus::Draft->value, InvoiceStatus::Cancelled->value),
        );

        $listener->onTransition($event);

        $lineTax = $invoice->getLines()->first()->getTaxes()->first();
        self::assertNull($lineTax->getSnapshottedAt());
    }

    public function testAlreadyFrozenSnapshotIsNotOverwritten(): void
    {
        $invoice = $this->buildInvoiceWithTaxedLine();
        $existingStamp = CarbonImmutable::parse('2025-01-01 12:00:00');
        $lineTax = $invoice->getLines()->first()->getTaxes()->first();
        $lineTax->freeze($existingStamp);

        $listener = new SnapshotTaxesOnIssueListener();
        $event = $this->makeEvent(
            $invoice,
            'invoice',
            new Transition('accept', InvoiceStatus::Draft->value, InvoiceStatus::Pending->value),
        );

        $listener->onTransition($event);

        self::assertSame($existingStamp, $lineTax->getSnapshottedAt());
    }

    public function testCalculatorRefusesToReSnapshotAFrozenLineTax(): void
    {
        $lineTax = new LineTax();
        $lineTax->setNameSnapshot('Initial');
        $lineTax->setRateSnapshot('10.0000');
        $lineTax->freeze(CarbonImmutable::now());

        $tax = new Tax()->setName('UpdatedAfterIssue');
        $tax->setRate(99.0)->setType(Tax::TYPE_EXCLUSIVE);

        $lineTax->snapshotFrom($tax);

        self::assertSame('Initial', $lineTax->getNameSnapshot());
        self::assertSame('10.0000', $lineTax->getRateSnapshot());
    }

    public function testInvoiceLevelInvoiceTaxIsFrozenOnTransition(): void
    {
        $invoice = $this->buildInvoiceWithTaxedLine();
        $invoiceTax = new InvoiceTax();
        $invoiceTax->setNameSnapshot('TDS');
        $invoiceTax->setRateSnapshot('10.0000');

        $invoice->addInvoiceTax($invoiceTax);

        $listener = new SnapshotTaxesOnIssueListener();
        $event = $this->makeEvent(
            $invoice,
            'invoice',
            new Transition('accept', InvoiceStatus::Draft->value, InvoiceStatus::Pending->value),
        );

        $listener->onTransition($event);

        self::assertInstanceOf(DateTimeImmutable::class, $invoiceTax->getSnapshottedAt());
    }

    private function buildInvoiceWithTaxedLine(): Invoice
    {
        $invoice = new Invoice();
        $line = new InvoiceLine();
        $line->setPrice(10000);
        $line->setQty(1);
        $line->updateTotal();

        $lineTax = new LineTax();
        $lineTax->setNameSnapshot('VAT');
        $lineTax->setRateSnapshot('20.0000');

        $line->addTax($lineTax);

        $invoice->addLine($line);

        return $invoice;
    }

    private function buildQuoteWithTaxedLine(): Quote
    {
        $quote = new Quote();
        $line = new QuoteLine();
        $line->setPrice(10000);
        $line->setQty(1);
        $line->updateTotal();

        $lineTax = new LineTax();
        $lineTax->setNameSnapshot('VAT');
        $lineTax->setRateSnapshot('20.0000');

        $line->addTax($lineTax);

        $quote->addLine($line);

        return $quote;
    }

    private function makeEvent(object $subject, string $workflow, Transition $transition): Event
    {
        $workflowMock = $this->createStub(WorkflowInterface::class);
        $workflowMock->method('getName')->willReturn($workflow);

        return new Event($subject, new Marking(), $transition, $workflowMock);
    }
}
