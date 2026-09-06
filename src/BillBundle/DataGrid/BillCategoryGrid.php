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

namespace Augias\BillBundle\DataGrid;

use Augias\BillBundle\Entity\BillCategory;
use Augias\BillBundle\Repository\BillCategoryRepository;
use Augias\DataGridBundle\Attributes\AsDataGrid;
use Augias\DataGridBundle\Grid;
use Augias\DataGridBundle\GridBuilder\Action\EditAction;
use Augias\DataGridBundle\GridBuilder\Batch\BatchAction;
use Augias\DataGridBundle\GridBuilder\Column\Column;
use Augias\DataGridBundle\GridBuilder\Column\StringColumn;
use Override;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @see \Augias\BillBundle\Tests\DataGrid\BillCategoryGridTest
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
