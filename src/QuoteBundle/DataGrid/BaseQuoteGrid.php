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

namespace SolidInvoice\QuoteBundle\DataGrid;

use Brick\Math\BigNumber;
use Money\Money;
use Override;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;

abstract class BaseQuoteGrid extends Grid
{
    public function __construct(
        protected readonly Calculator $calculator,
    ) {
    }

    public function entityFQCN(): string
    {
        return Quote::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('quoteId')
                ->label('quote.grid.quote_number')
                ->cellClass('col-id'),
            StringColumn::new('client')
                ->label('quote.grid.client')
                ->searchable(false)
                ->linkToRoute('_clients_view', ['id' => 'client.id']),
            MoneyColumn::new('total')
                ->label('quote.grid.total')
                ->formatValue(fn (BigNumber $value, Quote $quote) => new Money((string) $value, $quote->getClient()?->getCurrency())),
            StringColumn::new('status')
                ->label('quote.grid.status')
                ->twigFunction('quote_label')
                ->filter(ChoiceFilter::new('status', array_column(array_map(static fn (QuoteStatus $s) => [$s->value, $s->name], QuoteStatus::cases()), 1, 0))->multiple()),
            MoneyColumn::new('tax')
                ->label('quote.grid.tax')
                ->formatValue(fn (BigNumber $value, Quote $quote) => new Money((string) $value, $quote->getClient()?->getCurrency())),
            MoneyColumn::new('discount.value')
                ->label('quote.grid.discount')
                ->searchable(false)
                ->formatValue(function (float | BigNumber $value, Quote $quote): Money {
                    $discountAmount = $this->calculator->calculateDiscount($quote);

                    return new Money((string) $discountAmount, $quote->getClient()?->getCurrency());
                }),
            DateTimeColumn::new('created')
                ->label('quote.grid.created')
                ->format('d F Y')
                ->filter(new DateRangeFilter('created'))
        ];
    }

    /**
     * @return Action[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            ViewAction::new('_quotes_view', ['id' => 'id']),
            EditAction::new('_quotes_edit', ['id' => 'id']),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        yield BatchAction::new('Delete')
            ->icon('trash')
            ->color('danger')
            ->action(static function (QuoteRepository $repository, array $selectedItems): void {
                $repository->deleteQuotes($selectedItems);
            });
    }
}
