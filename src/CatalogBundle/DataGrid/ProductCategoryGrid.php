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

namespace Augias\CatalogBundle\DataGrid;

use Augias\CatalogBundle\Entity\ProductCategory;
use Augias\CatalogBundle\Repository\ProductCategoryRepository;
use Augias\DataGridBundle\Attributes\AsDataGrid;
use Augias\DataGridBundle\Grid;
use Augias\DataGridBundle\GridBuilder\Action\EditAction;
use Augias\DataGridBundle\GridBuilder\Batch\BatchAction;
use Augias\DataGridBundle\GridBuilder\Column\Column;
use Augias\DataGridBundle\GridBuilder\Column\StringColumn;
use Override;

#[AsDataGrid(name: 'catalog_category_grid', title: 'catalog.category.grid.title')]
final class ProductCategoryGrid extends Grid
{
    public function entityFQCN(): string
    {
        return ProductCategory::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('name')
                ->label('catalog.category.grid.name'),
        ];
    }

    /**
     * @return EditAction[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            EditAction::new('_catalog_categories_edit', ['id' => 'id']),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        // Products keep their category via ON DELETE SET NULL, so removing a
        // category never takes catalogue entries with it.
        yield BatchAction::new('Delete')
            ->icon('trash')
            ->color('danger')
            ->action(static function (ProductCategoryRepository $repository, array $selectedItems): void {
                $repository->deleteCategories($selectedItems);
            });
    }
}
