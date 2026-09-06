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

namespace SolidInvoice\ElectronicInvoicingBundle\DataGrid;

use Override;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceReceipt;
use Symfony\Component\Translation\TranslatableMessage;
use function str_replace;
use function ucwords;

/**
 * @see \SolidInvoice\ElectronicInvoicingBundle\Tests\DataGrid\IncomingElectronicInvoiceGridTest
 */
#[AsDataGrid(name: 'einvoicing_incoming_grid', title: 'Received Electronic Invoices')]
final class IncomingElectronicInvoiceGrid extends Grid
{
    public function entityFQCN(): string
    {
        return ElectronicInvoiceReceipt::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('sellerName')
                ->label('einvoicing.grid.seller_name'),
            StringColumn::new('invoiceNumber')
                ->label('einvoicing.grid.invoice_number'),
            StringColumn::new('provider')
                ->label('einvoicing.grid.provider')
                ->formatValue(static fn (string $value): string => ucwords(str_replace('_', ' ', $value))),
            DateTimeColumn::new('issueDate')
                ->label('einvoicing.grid.issue_date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('issueDate')),
            MoneyColumn::new('amount')
                ->label('einvoicing.grid.amount')
                ->searchable(false)
                ->sortableField('totalAmount'),
            DateTimeColumn::new('created')
                ->label('einvoicing.grid.received')
                ->format('d F Y')
                ->filter(new DateRangeFilter('created')),
        ];
    }

    /**
     * @return Action[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            Action::new('_einvoicing_incoming_download', ['id' => 'id'])
                ->icon('download')
                ->label('Download'),
            // Idempotent: opens the existing Bill if this receipt was already
            // converted, otherwise creates one — see BillBundle\Action\CreateFromReceipt.
            Action::new('_bills_create_from_receipt', ['id' => 'id'])
                ->icon('receipt')
                ->label('Create Bill')
                ->inMenu(),
        ];
    }

    public function getCreateRoute(): ?string
    {
        // Received invoices arrive automatically from the active provider —
        // there's nothing for a user to manually create here, unlike every
        // other grid that links this to an add/create action.
        return null;
    }

    #[Override]
    public function getEmptyTitle(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.einvoicing_incoming.title');
    }

    #[Override]
    public function getEmptyDescription(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.einvoicing_incoming.description');
    }
}
