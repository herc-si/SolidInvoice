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

namespace Augias\InvoiceBundle\Search;

use Augias\CoreBundle\Search\QualifiedResultFormatterInterface;
use Augias\CoreBundle\Search\SearchResult;
use Augias\MoneyBundle\Formatter\MoneyFormatterInterface;
use Augias\SettingsBundle\SystemConfig;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Money;
use Symfony\Component\Routing\RouterInterface;

/**
 * @see \Augias\InvoiceBundle\Tests\Search\RecurringInvoiceResultFormatterTest
 */
final readonly class RecurringInvoiceResultFormatter implements QualifiedResultFormatterInterface
{
    public function __construct(
        private RouterInterface $router,
        private MoneyFormatterInterface $moneyFormatter,
        private SystemConfig $systemConfig,
    ) {
    }

    public function getSupportedQualifiers(): array
    {
        return [
            'status' => 'status',
            'amount' => 'total',
            'client' => 'client.name',
        ];
    }

    public function getIndexName(): string
    {
        return 'recurring_invoices';
    }

    public function format(array $hit): SearchResult
    {
        return new SearchResult(
            type: 'recurring_invoice',
            id: $hit['id'],
            title: $hit['client']['name'] ?? $hit['id'],
            subtitle: $hit['status'] ?? '',
            url: $this->router->generate('_invoices_view_recurring', ['id' => $hit['id']]),
            status: $hit['status'] ?? null,
            meta: isset($hit['total'])
                ? $this->moneyFormatter->format(new Money(
                    BigDecimal::of((string) $hit['total'])->multipliedBy(100)->toScale(0, RoundingMode::HalfEven)->toInt(),
                    new Currency($hit['client']['currencyCode'] ?? $this->systemConfig->getCurrency()->getCode()),
                ))
                : null,
        );
    }
}
