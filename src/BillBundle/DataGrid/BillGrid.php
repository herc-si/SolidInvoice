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

namespace SolidInvoice\BillBundle\DataGrid;

use Doctrine\ORM\EntityManagerInterface;
use Override;
use SolidInvoice\BillBundle\Entity\Bill;
use SolidInvoice\BillBundle\Enum\BillStatus;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Translation\TranslatableMessage;
use function array_column;
use function array_key_exists;
use function array_map;

/**
 * @see \SolidInvoice\BillBundle\Tests\DataGrid\BillGridTest
 */
#[AsDataGrid(name: 'bill_grid', title: 'Bills')]
final class BillGrid extends Grid
{
    public function entityFQCN(): string
    {
        return Bill::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('supplier')
                ->label('bill.grid.supplier')
                ->searchable(false)
                ->linkToRoute('_suppliers_view', ['id' => 'supplier.id']),
            StringColumn::new('billNumber')
                ->label('bill.grid.bill_number'),
            StringColumn::new('status')
                ->label('bill.grid.status')
                ->twigFunction('bill_label')
                ->filter(ChoiceFilter::new('status', array_column(array_map(static fn (BillStatus $s) => [$s->value, $s->getLabel()], BillStatus::cases()), 1, 0))->multiple()),
            MoneyColumn::new('total')
                ->label('bill.grid.total')
                ->searchable(false)
                ->sortableField('totalAmount'),
            MoneyColumn::new('balance')
                ->label('bill.grid.balance')
                ->searchable(false)
                ->sortableField('totalAmount'),
            RelativeDateColumn::new('dueDate')
                ->label('bill.grid.due_date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('dueDate'))
                ->hiddenByDefault(),
            StringColumn::new('category')
                ->label('bill.grid.category')
                ->searchable(false)
                ->hiddenByDefault(),
            RelativeDateColumn::new('issueDate')
                ->label('bill.grid.issue_date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('issueDate'))
                ->hiddenByDefault(),
        ];
    }

    /**
     * @return list<ViewAction|EditAction|Action>
     */
    #[Override]
    public function actions(): array
    {
        return [
            ViewAction::new('_bills_view', ['id' => 'id']),
            EditAction::new('_bills_edit', ['id' => 'id']),
            Action::new('_bills_record_payment', ['id' => 'id'])
                ->icon('cash')
                ->label('Record Payment')
                ->inMenu(),
        ];
    }

    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query = parent::query($entityManager, $query);

        if (array_key_exists('supplier_id', $this->context) && null !== $this->context['supplier_id']) {
            $query
                ->getQueryBuilder()
                ->andWhere(ORMSource::ALIAS . '.supplier = :supplier_id')
                ->setParameter('supplier_id', $this->context['supplier_id'], UlidType::NAME);
        }

        $query->getQueryBuilder()->orderBy(ORMSource::ALIAS . '.issueDate', 'DESC');

        return $query;
    }

    public function getCreateRoute(): ?string
    {
        return '_bills_add';
    }

    #[Override]
    public function getCreateLabel(): ?TranslatableMessage
    {
        return new TranslatableMessage('bill.grid.create');
    }

    #[Override]
    public function getEmptyTitle(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.bill.title');
    }

    #[Override]
    public function getEmptyDescription(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.bill.description');
    }
}
