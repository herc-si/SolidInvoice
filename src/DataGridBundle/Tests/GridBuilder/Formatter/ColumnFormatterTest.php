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

namespace Augias\DataGridBundle\Tests\GridBuilder\Formatter;

use Augias\DataGridBundle\GridBuilder\Column\CurrencyColumn;
use Augias\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use Augias\DataGridBundle\GridBuilder\Column\MoneyColumn;
use Augias\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use Augias\DataGridBundle\GridBuilder\Column\StatusColumn;
use Augias\DataGridBundle\GridBuilder\Column\StringColumn;
use Augias\DataGridBundle\GridBuilder\Column\UrlColumn;
use Augias\DataGridBundle\GridBuilder\Formatter\ColumnFormatter;
use Augias\DataGridBundle\GridBuilder\Formatter\CurrencyFormatter;
use Augias\DataGridBundle\GridBuilder\Formatter\DateTimeFormatter;
use Augias\DataGridBundle\GridBuilder\Formatter\MoneyFormatter;
use Augias\DataGridBundle\GridBuilder\Formatter\RelativeDateFormatter;
use Augias\DataGridBundle\GridBuilder\Formatter\StatusFormatter;
use Augias\DataGridBundle\GridBuilder\Formatter\StringFormatter;
use Augias\DataGridBundle\GridBuilder\Formatter\UrlFormatter;
use Augias\SettingsBundle\SystemConfig;
use Mockery as M;
use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[CoversClass(ColumnFormatter::class)]
final class ColumnFormatterTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private ColumnFormatter $formatter;

    /**
     * @var ServiceLocator<string>&Stub
     */
    private ServiceLocator & Stub $locator;

    protected function setUp(): void
    {
        $this->locator = $this->createStub(ServiceLocator::class);
        $this->formatter = new ColumnFormatter($this->locator);
    }

    public function testFormatReturnsCorrectValueForSupportedColumn(): void
    {
        $column = CurrencyColumn::new('currency');
        $config = M::mock(SystemConfig::class);
        $config->expects()
            ->getCurrency()
            ->andReturn(new Currency('USD'));

        $formatter = new CurrencyFormatter($config, 'en_US');

        $this->locator->method('has')->willReturn(true);
        $this->locator->method('get')->willReturn($formatter);

        self::assertSame('US Dollar', $this->formatter->format($column, 'USD'));
    }

    public function testFormatReturnsCorrectValueForUnsupportedColumn(): void
    {
        $column = StringColumn::new('test');
        $formatter = new StringFormatter(new Environment(new ArrayLoader()));

        $this->locator->method('has')->willReturn(false);
        $this->locator->method('get')->willReturn($formatter);

        self::assertSame('value', $this->formatter->format($column, 'value'));
    }

    public function testGetSubscribedServicesReturnsCorrectServices(): void
    {
        $services = ColumnFormatter::getSubscribedServices();

        self::assertSame([
            CurrencyColumn::class => CurrencyFormatter::class,
            DateTimeColumn::class => DateTimeFormatter::class,
            MoneyColumn::class => MoneyFormatter::class,
            RelativeDateColumn::class => RelativeDateFormatter::class,
            StatusColumn::class => StatusFormatter::class,
            StringColumn::class => StringFormatter::class,
            UrlColumn::class => UrlFormatter::class,
        ], $services);
    }
}
