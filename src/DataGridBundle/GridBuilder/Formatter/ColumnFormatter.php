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

namespace Augias\DataGridBundle\GridBuilder\Formatter;

use Augias\DataGridBundle\GridBuilder\Column\Column;
use Augias\DataGridBundle\GridBuilder\Column\CurrencyColumn;
use Augias\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use Augias\DataGridBundle\GridBuilder\Column\MoneyColumn;
use Augias\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use Augias\DataGridBundle\GridBuilder\Column\StatusColumn;
use Augias\DataGridBundle\GridBuilder\Column\StringColumn;
use Augias\DataGridBundle\GridBuilder\Column\UrlColumn;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @see \Augias\DataGridBundle\Tests\GridBuilder\Formatter\ColumnFormatterTest
 */
final readonly class ColumnFormatter implements ServiceSubscriberInterface, FormatterInterface
{
    /**
     * @param ServiceLocator<FormatterInterface> $locator
     */
    public function __construct(
        private ServiceProviderInterface $locator
    ) {
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function format(Column $column, mixed $value): string | TranslatableMessage
    {
        if (! $this->locator->has($column::class)) {
            // @phpstan-ignore-next-line
            return $this->locator->get(StringColumn::class)->format($column, $value);
        }

        return $this->locator->get($column::class)->format($column, $value);
    }

    /**
     * @return array<class-string, class-string>
     */
    public static function getSubscribedServices(): array
    {
        return [
            CurrencyColumn::class => CurrencyFormatter::class,
            DateTimeColumn::class => DateTimeFormatter::class,
            MoneyColumn::class => MoneyFormatter::class,
            RelativeDateColumn::class => RelativeDateFormatter::class,
            StatusColumn::class => StatusFormatter::class,
            StringColumn::class => StringFormatter::class,
            UrlColumn::class => UrlFormatter::class,
        ];
    }
}
