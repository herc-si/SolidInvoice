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

use Override;
use SolidInvoice\BillBundle\Entity\BillCategory;
use SolidInvoice\BillBundle\Repository\BillCategoryRepository;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @see \SolidInvoice\BillBundle\Tests\DataGrid\BillCategoryGridTest
 */
#[AsDataGrid(name: 'bill_category_grid', title: 'Bill Categories')]
final class BillCategoryGrid extends Grid
{
    public function entityFQCN(): string
    {
        return BillCategory::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('name')
                ->label('bill.category.grid.name'),
        ];
    }

    /**
     * @return EditAction[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            EditAction::new('_bill_categories_edit', ['id' => 'id']),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        yield BatchAction::new('Delete')
            ->icon('trash')
            ->color('danger')
            ->confirmMessage('Are you sure you want to delete the selected bill categories?')
            ->action(static function (BillCategoryRepository $repository, array $selectedItems): void {
                foreach ($selectedItems as $id) {
                    $category = $repository->find($id);

                    if ($category instanceof BillCategory) {
                        $repository->delete($category);
                    }
                }
            });
    }

    public function getCreateRoute(): ?string
    {
        return '_bill_categories_add';
    }

    #[Override]
    public function getCreateLabel(): ?TranslatableMessage
    {
        return new TranslatableMessage('bill.category.grid.create');
    }

    #[Override]
    public function getEmptyTitle(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.bill_category.title');
    }

    #[Override]
    public function getEmptyDescription(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.bill_category.description');
    }
}
