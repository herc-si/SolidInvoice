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

namespace Augias\DashboardBundle\Tests\Widgets;

use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\CoreBundle\Entity\Discount;
use Augias\DashboardBundle\Tests\Fixtures\StubAttentionSource;
use Augias\DashboardBundle\Widgets\AttentionRequiredWidget;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Enum\RecurringInvoiceStatus;
use Augias\InvoiceBundle\Test\Factory\InvoiceFactory;
use Augias\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use Augias\QuoteBundle\Enum\QuoteStatus;
use Augias\QuoteBundle\Test\Factory\QuoteFactory;
use Brick\Math\BigInteger;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\NullLogger;
use RuntimeException;

final class AttentionRequiredWidgetTest extends WidgetTestCase
{
    private function widgetWith(StubAttentionSource ...$sources): AttentionRequiredWidget
    {
        $registry = self::getContainer()->get(ManagerRegistry::class);
        self::assertInstanceOf(ManagerRegistry::class, $registry);

        return new AttentionRequiredWidget($registry, $sources, new NullLogger());
    }

    private function createZeroDiscount(): Discount
    {
        return new Discount()
            ->setType('percentage')
            ->setValueMoney(BigInteger::zero())
            ->setValuePercentage(0);
    }

    /**
     * Another bundle's section is folded into this card rather than given a card
     * of its own, and it decides its own markup: the dashboard only carries the
     * template name and the data across.
     */
    public function testFoldsInASectionAnotherBundleContributed(): void
    {
        $data = $this->widgetWith(new StubAttentionSource(
            template: 'accounting_section.html.twig',
            data: ['crossed' => 2],
        ))->getData();

        self::assertSame(
            [['template' => 'accounting_section.html.twig', 'data' => ['crossed' => 2]]],
            $data['sections'],
        );

        // The card is no longer "All caught up" just because the invoices are.
        self::assertTrue($data['hasItems']);
    }

    public function testASourceThatDoesNotApplyContributesNothing(): void
    {
        $data = $this->widgetWith(new StubAttentionSource(supported: false))->getData();

        self::assertSame([], $data['sections']);
        self::assertFalse($data['hasItems']);
    }

    public function testASourceWithNothingToSayContributesNoEmptyHeader(): void
    {
        $data = $this->widgetWith(new StubAttentionSource(hasItems: false))->getData();

        self::assertSame([], $data['sections']);
        self::assertFalse($data['hasItems']);
    }

    /**
     * This card is the invoices one first. A contributor that falls over is
     * skipped, because an outage in somebody else's bundle has no business
     * emptying the list of what is overdue.
     */
    public function testAFailingSourceDoesNotTakeTheCardDownWithIt(): void
    {
        $data = $this->widgetWith(new StubAttentionSource(failure: new RuntimeException('accounting is down')))
            ->getData();

        self::assertSame([], $data['sections']);
        self::assertArrayHasKey('overdueInvoices', $data);
    }

    public function testGetDataReturnsCorrectStructure(): void
    {
        $widget = self::getContainer()->get(AttentionRequiredWidget::class);

        $data = $widget->getData();

        self::assertArrayHasKey('overdueInvoices', $data);
        self::assertArrayHasKey('draftInvoices', $data);
        self::assertArrayHasKey('pendingQuotes', $data);
        self::assertArrayHasKey('upcomingRecurring', $data);
        self::assertArrayHasKey('hasItems', $data);
    }

    public function testGetDataWithNoData(): void
    {
        $widget = self::getContainer()->get(AttentionRequiredWidget::class);

        $data = $widget->getData();

        self::assertSame([], $data['overdueInvoices']);
        self::assertSame([], $data['draftInvoices']);
        self::assertSame([], $data['pendingQuotes']);
        self::assertSame([], $data['upcomingRecurring']);
        self::assertFalse($data['hasItems']);
    }

    public function testGetDataWithOverdueInvoices(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        InvoiceFactory::createMany(3, [
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'balance' => BigInteger::of(10000),
            'total' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'due' => CarbonImmutable::now()->subDays(5),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);
        $data = $widget->getData();

        self::assertCount(3, $data['overdueInvoices']);
        self::assertTrue($data['hasItems']);
    }

    public function testGetDataWithDraftInvoices(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        InvoiceFactory::createMany(2, [
            'client' => $client,
            'status' => InvoiceStatus::Draft,
            'total' => BigInteger::of(5000),
            'balance' => BigInteger::of(5000),
            'baseTotal' => BigInteger::of(5000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);
        $data = $widget->getData();

        self::assertCount(2, $data['draftInvoices']);
        self::assertTrue($data['hasItems']);
    }

    public function testGetDataWithPendingQuotes(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        QuoteFactory::createMany(4, [
            'client' => $client,
            'company' => $this->company,
            'status' => QuoteStatus::Pending,
            'total' => BigInteger::of(7500),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);
        $data = $widget->getData();

        self::assertCount(4, $data['pendingQuotes']);
        self::assertTrue($data['hasItems']);
    }

    public function testGetDataWithUpcomingRecurring(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        RecurringInvoiceFactory::createMany(2, [
            'client' => $client,
            'status' => RecurringInvoiceStatus::Active,
            'dateStart' => CarbonImmutable::now()->addDays(3),
            'total' => BigInteger::of(15000),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);
        $data = $widget->getData();

        self::assertCount(2, $data['upcomingRecurring']);
        self::assertTrue($data['hasItems']);
    }

    public function testGetDataLimitsResults(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        // Create more than the limit (5)
        InvoiceFactory::createMany(10, [
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'balance' => BigInteger::of(10000),
            'total' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);
        $data = $widget->getData();

        // Should be limited to 5
        self::assertCount(5, $data['overdueInvoices']);
    }

    public function testGetTemplate(): void
    {
        $widget = self::getContainer()->get(AttentionRequiredWidget::class);

        self::assertSame('@AugiasDashboard/Widget/attention_required.html.twig', $widget->getTemplate());
    }

    public function testRenderWidgetWithNoData(): void
    {
        $widget = self::getContainer()->get(AttentionRequiredWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }

    public function testRenderWidgetWithAllSections(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
            'name' => 'Test Client',
        ]);

        // Create overdue invoice
        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'balance' => BigInteger::of(10000),
            'total' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'invoiceId' => 'INV-001',
            'due' => CarbonImmutable::parse('2024-01-15'),
        ]);

        // Create draft invoice
        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Draft,
            'total' => BigInteger::of(5000),
            'balance' => BigInteger::of(5000),
            'baseTotal' => BigInteger::of(5000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        // Create pending quote
        QuoteFactory::createOne([
            'client' => $client,
            'company' => $this->company,
            'status' => QuoteStatus::Pending,
            'total' => BigInteger::of(7500),
            'baseTotal' => BigInteger::of(7500),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
            'quoteId' => 'QUO-001',
            'created' => Carbon::parse('2024-01-10'),
        ]);

        // Create upcoming recurring
        RecurringInvoiceFactory::createOne([
            'client' => $client,
            'status' => RecurringInvoiceStatus::Active,
            'dateStart' => CarbonImmutable::parse('2024-01-20'),
            'total' => BigInteger::of(15000),
            'baseTotal' => BigInteger::of(15000),
            'tax' => BigInteger::zero(),
            'discount' => $this->createZeroDiscount(),
        ]);

        $widget = self::getContainer()->get(AttentionRequiredWidget::class);

        $html = $this->renderWidget($widget);

        $this->assertMatchesHtmlSnapshot($html);
    }
}
