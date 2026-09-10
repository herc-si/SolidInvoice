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
use Augias\DashboardBundle\Widgets\OutstandingTotalWidget;
use Augias\InvoiceBundle\Entity\Line;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Test\Factory\InvoiceFactory;
use Brick\Math\BigInteger;

final class OutstandingTotalWidgetTest extends WidgetTestCase
{
    public function testGetDataReturnsCorrectStructure(): void
    {
        $data = $this->widget()->getData();

        self::assertArrayHasKey('totalOutstanding', $data);
        self::assertArrayHasKey('defaultCurrency', $data);
    }

    public function testGetDataWithNoData(): void
    {
        self::assertSame([], $this->widget()->getData()['totalOutstanding']);
    }

    public function testGetDataSumsWhatIsStillOwed(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        InvoiceFactory::createMany(2, [
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'balance' => BigInteger::of(5000),
            'total' => BigInteger::of(5000),
            'baseTotal' => BigInteger::of(5000),
            'tax' => BigInteger::zero(),
            'discount' => $this->zeroDiscount(),
            'lines' => [
                new Line()
                    ->setDescription('Test Item')
                    ->setQty(1)
                    ->setPrice(5000),
            ],
        ]);

        $data = $this->widget()->getData();

        self::assertArrayHasKey('USD', $data['totalOutstanding']);
        self::assertSame('10000', (string) $data['totalOutstanding']['USD']);
    }

    public function testGetTemplate(): void
    {
        self::assertSame('@AugiasDashboard/Widget/stat_outstanding.html.twig', $this->widget()->getTemplate());
    }

    public function testRenderWidgetWithNoData(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderWidget($this->widget()));
    }

    public function testRenderWidgetWithData(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        InvoiceFactory::createOne([
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'balance' => BigInteger::of(10000),
            'total' => BigInteger::of(10000),
            'baseTotal' => BigInteger::of(10000),
            'tax' => BigInteger::zero(),
            'discount' => $this->zeroDiscount(),
        ]);

        $this->assertMatchesHtmlSnapshot($this->renderWidget($this->widget()));
    }

    private function widget(): OutstandingTotalWidget
    {
        $widget = self::getContainer()->get(OutstandingTotalWidget::class);
        self::assertInstanceOf(OutstandingTotalWidget::class, $widget);

        return $widget;
    }

    private function zeroDiscount(): Discount
    {
        return new Discount()
            ->setType('percentage')
            ->setValueMoney(BigInteger::zero())
            ->setValuePercentage(0);
    }
}
