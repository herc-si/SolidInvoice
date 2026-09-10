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
use Augias\DashboardBundle\Widgets\PaymentsThisMonthWidget;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Test\Factory\InvoiceFactory;
use Augias\PaymentBundle\Enum\PaymentStatus;
use Augias\PaymentBundle\Test\Factory\PaymentFactory;
use Augias\PaymentBundle\Test\Factory\PaymentMethodFactory;
use Brick\Math\BigInteger;
use Carbon\Carbon;

final class PaymentsThisMonthWidgetTest extends WidgetTestCase
{
    public function testGetDataReturnsCorrectStructure(): void
    {
        $data = $this->widget()->getData();

        self::assertArrayHasKey('paymentsThisMonth', $data);
        self::assertArrayHasKey('defaultCurrency', $data);
    }

    public function testGetDataWithNoData(): void
    {
        self::assertSame([], $this->widget()->getData()['paymentsThisMonth']);
    }

    public function testGetDataTotalsThisMonthsPayments(): void
    {
        $this->createCapturedPayment(50000);

        $data = $this->widget()->getData();

        self::assertArrayHasKey('USD', $data['paymentsThisMonth']);
        self::assertSame('50000', (string) $data['paymentsThisMonth']['USD']);
    }

    public function testGetTemplate(): void
    {
        self::assertSame('@AugiasDashboard/Widget/stat_payments_this_month.html.twig', $this->widget()->getTemplate());
    }

    public function testRenderWidgetWithNoData(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderWidget($this->widget()));
    }

    public function testRenderWidgetWithData(): void
    {
        $this->createCapturedPayment(25000);

        $this->assertMatchesHtmlSnapshot($this->renderWidget($this->widget()));
    }

    private function createCapturedPayment(int $amount): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'total' => BigInteger::of($amount),
            'balance' => BigInteger::zero(),
            'baseTotal' => BigInteger::of($amount),
            'tax' => BigInteger::zero(),
            'discount' => new Discount()
                ->setType('percentage')
                ->setValueMoney(BigInteger::zero())
                ->setValuePercentage(0),
        ]);

        PaymentFactory::createOne([
            'client' => $client,
            'invoice' => $invoice,
            'method' => PaymentMethodFactory::createOne(['company' => $this->company]),
            'totalAmount' => $amount,
            'currencyCode' => 'USD',
            'status' => PaymentStatus::Captured,
            'created' => Carbon::now(),
        ]);
    }

    private function widget(): PaymentsThisMonthWidget
    {
        $widget = self::getContainer()->get(PaymentsThisMonthWidget::class);
        self::assertInstanceOf(PaymentsThisMonthWidget::class, $widget);

        return $widget;
    }
}
