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
use Augias\DashboardBundle\Widgets\OverdueInvoicesWidget;
use Augias\InvoiceBundle\Entity\Line;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Test\Factory\InvoiceFactory;
use Brick\Math\BigInteger;

final class OverdueInvoicesWidgetTest extends WidgetTestCase
{
    public function testGetDataReturnsCorrectStructure(): void
    {
        $data = $this->widget()->getData();

        self::assertArrayHasKey('overdueCount', $data);
        self::assertArrayHasKey('overdueAmount', $data);
        self::assertArrayHasKey('defaultCurrency', $data);
    }

    public function testGetDataWithNoData(): void
    {
        $data = $this->widget()->getData();

        self::assertSame(0, $data['overdueCount']);
        self::assertSame([], $data['overdueAmount']);
    }

    public function testGetDataCountsAndTotalsWhatIsLate(): void
    {
        $this->createOverdueInvoices(3, 10000);

        $data = $this->widget()->getData();

        self::assertSame(3, $data['overdueCount']);
        self::assertArrayHasKey('USD', $data['overdueAmount']);
        self::assertSame('30000', (string) $data['overdueAmount']['USD']);
    }

    public function testGetTemplate(): void
    {
        self::assertSame('@AugiasDashboard/Widget/stat_overdue.html.twig', $this->widget()->getTemplate());
    }

    /**
     * Zero overdue is rendered without the danger tint: a red card congratulating
     * a healthy account is the same defect as a $0.00 total in success green,
     * mirrored. The snapshot is what holds that.
     */
    public function testRenderWidgetWithNoData(): void
    {
        $this->assertMatchesHtmlSnapshot($this->renderWidget($this->widget()));
    }

    public function testRenderWidgetWithData(): void
    {
        $this->createOverdueInvoices(2, 15000);

        $this->assertMatchesHtmlSnapshot($this->renderWidget($this->widget()));
    }

    private function createOverdueInvoices(int $count, int $amount): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        InvoiceFactory::createMany($count, [
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'balance' => BigInteger::of($amount),
            'total' => BigInteger::of($amount),
            'baseTotal' => BigInteger::of($amount),
            'tax' => BigInteger::zero(),
            'discount' => new Discount()
                ->setType('percentage')
                ->setValueMoney(BigInteger::zero())
                ->setValuePercentage(0),
            'lines' => [
                new Line()
                    ->setDescription('Test Item')
                    ->setQty(1)
                    ->setPrice($amount),
            ],
        ]);
    }

    private function widget(): OverdueInvoicesWidget
    {
        $widget = self::getContainer()->get(OverdueInvoicesWidget::class);
        self::assertInstanceOf(OverdueInvoicesWidget::class, $widget);

        return $widget;
    }
}
