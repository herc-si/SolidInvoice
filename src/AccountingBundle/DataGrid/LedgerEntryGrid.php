<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\AccountingBundle\DataGrid;

use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\DataGridBundle\Attributes\AsDataGrid;
use Augias\DataGridBundle\Grid;
use Augias\DataGridBundle\GridBuilder\Action\Action;
use Augias\DataGridBundle\GridBuilder\Action\EditAction;
use Augias\DataGridBundle\GridBuilder\Column\Column;
use Augias\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use Augias\DataGridBundle\GridBuilder\Column\MoneyColumn;
use Augias\DataGridBundle\GridBuilder\Column\StringColumn;
use Augias\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use Augias\DataGridBundle\GridBuilder\Query;
use Augias\DataGridBundle\Source\ORMSource;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use function array_key_exists;

/**
 * One statutory book, shown as it has to read: chronological, with the
 * columns the register is required to carry — date, nature of the operation,
 * counterparty, supporting document, amount and method of settlement.
 *
 * Both books share this grid and are told apart by the `book` context, which
 * every route that renders it sets. Without one the grid would mix receipts and
 * purchases into a single list that is neither register.
 *
 * @see \Augias\AccountingBundle\Tests\DataGrid\LedgerEntryGridTest
 */
#[AsDataGrid(name: 'ledger_entry_grid', title: 'Ledger')]
final class LedgerEntryGrid extends Grid
{
    public function entityFQCN(): string
    {
        return LedgerEntry::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('sequenceNumber')
                ->label('accounting.entry.grid.number')
                ->searchable(false),
            DateTimeColumn::new('entryDate')
                ->label('accounting.entry.grid.date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('entryDate')),
            StringColumn::new('label')
                ->label('accounting.entry.grid.label'),
            StringColumn::new('counterpartyName')
                ->label('accounting.entry.grid.counterparty'),
            StringColumn::new('documentReference')
                ->label('accounting.entry.grid.document_reference'),
            MoneyColumn::new('money')
                ->label('accounting.entry.grid.amount')
                ->searchable(false)
                ->sortableField('amount'),
        ];
    }

    /**
     * @return list<EditAction|Action>
     */
    #[Override]
    public function actions(): array
    {
        return [
            EditAction::new('_accounting_entry_edit', ['id' => 'id']),
        ];
    }

    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query = parent::query($entityManager, $query);

        $book = array_key_exists('book', $this->context) ? $this->context['book'] : LedgerBook::Revenue->value;

        $query->getQueryBuilder()
            ->andWhere(ORMSource::ALIAS . '.book = :book')
            ->setParameter('book', $book)
            // The order the register is read and numbered in. Created is the
            // tie-break so two entries on the same day keep the order they were
            // written, which is the order they will be sealed in.
            ->orderBy(ORMSource::ALIAS . '.entryDate', 'DESC')
            ->addOrderBy(ORMSource::ALIAS . '.created', 'DESC');

        return $query;
    }
}
