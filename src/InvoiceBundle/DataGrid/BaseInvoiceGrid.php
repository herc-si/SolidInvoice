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

namespace SolidInvoice\InvoiceBundle\DataGrid;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use Override;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\MoneyBundle\Calculator;

abstract class BaseInvoiceGrid extends Grid
{
    public function __construct(
        protected readonly Calculator $calculator,
    ) {
    }

    public function entityFQCN(): string
    {
        return Invoice::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            // Default-visible: the columns needed to scan and act on a list of
            // invoices without scrolling. Everything below stays reachable via
            // the column-visibility picker, just not shown until asked for —
            // with all 11 columns visible at once the row actions were only
            // reachable after scrolling the table horizontally.
            StringColumn::new('invoiceId')
                ->label('invoice.grid.invoice_number'),
            StringColumn::new('client')
                ->label('invoice.grid.client')
                ->searchable(false)
                ->linkToRoute('_clients_view', ['id' => 'client.id']),
            StringColumn::new('status')
                ->label('invoice.grid.status')
                ->twigFunction('invoice_label')
                ->filter(ChoiceFilter::new('status', array_column(array_map(static fn (InvoiceStatus $s) => [$s->value, $s->getLabel()], InvoiceStatus::cases()), 1, 0))->multiple()),
            MoneyColumn::new('total')
                ->label('invoice.grid.total')
                ->formatValue(fn (BigNumber $value, Invoice $invoice) => new Money((string) $value, $invoice->getClient()?->getCurrency())),
            MoneyColumn::new('balance')
                ->label('invoice.grid.balance')
                ->formatValue(fn (BigNumber $value, Invoice $invoice) => new Money((string) $value, $invoice->getClient()?->getCurrency())),
            RelativeDateColumn::new('due')
                ->label('invoice.grid.due_date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('due')),

            // Hidden by default: secondary detail, one toggle away.
            RelativeDateColumn::new('invoiceDate')
                ->label('invoice.grid.invoice_date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('invoiceDate'))
                ->hiddenByDefault(),
            RelativeDateColumn::new('paidDate')
                ->label('invoice.grid.paid_date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('paidDate'))
                ->hiddenByDefault(),
            MoneyColumn::new('tax')
                ->label('invoice.grid.tax')
                ->formatValue(fn (BigNumber $value, Invoice $invoice) => new Money((string) $value, $invoice->getClient()?->getCurrency()))
                ->hiddenByDefault(),
            MoneyColumn::new('payableAmount')
                ->label('invoice.grid.payable')
                ->searchable(false)
                ->formatValue(function (BigNumber $value, Invoice $invoice): Money {
                    $client = $invoice->getClient();
                    // Render the explicit payable figure only when withholding is in
                    // play; otherwise mirror the grand total so the column stays
                    // meaningful for invoices without TDS-style deductions.
                    $withholding = $invoice->getWithholdingAmount();
                    $amount = $withholding->isPositive() ? $value : $invoice->getTotal();

                    return new Money((string) $amount, $client?->getCurrency());
                })
                ->hiddenByDefault(),
            MoneyColumn::new('discount.value')
                ->label('invoice.grid.discount')
                ->searchable(false)
                ->formatValue(function (float | BigNumber $value, Invoice $invoice): Money {
                    $discountAmount = $this->calculator->calculateDiscount($invoice);

                    return new Money((string) $discountAmount->toScale(0, RoundingMode::HalfUp), $invoice->getClient()?->getCurrency());
                })
                ->hiddenByDefault(),
        ];
    }

    /**
     * @return Action[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            ViewAction::new('_invoices_view', ['id' => 'id']),
            EditAction::new('_invoices_edit', ['id' => 'id']),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        yield BatchAction::new('Delete')
            ->icon('trash')
            ->color('danger')
            ->action(static function (InvoiceRepository $repository, array $selectedItems): void {
                $repository->deleteInvoices($selectedItems);
            });
    }

    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query->getQueryBuilder()->orderBy(ORMSource::ALIAS . '.invoiceDate', 'DESC');

        return $query;
    }
}
