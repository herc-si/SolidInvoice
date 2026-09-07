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

namespace Augias\CoreBundle\DataGrid;

use Augias\CoreBundle\Entity\Category;
use Augias\CoreBundle\Enum\CategoryUsage;
use Augias\CoreBundle\Repository\CategoryRepository;
use Augias\DataGridBundle\Attributes\AsDataGrid;
use Augias\DataGridBundle\Grid;
use Augias\DataGridBundle\GridBuilder\Action\EditAction;
use Augias\DataGridBundle\GridBuilder\Batch\BatchAction;
use Augias\DataGridBundle\GridBuilder\Column\Column;
use Augias\DataGridBundle\GridBuilder\Column\StringColumn;
use Augias\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use Override;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @see \Augias\CoreBundle\Tests\DataGrid\CategoryGridTest
 */
#[AsDataGrid(name: 'category_grid', title: 'Categories')]
final class CategoryGrid extends Grid
{
    public function entityFQCN(): string
    {
        return Category::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('name')
                ->label('category.grid.name'),
            // Shown as columns rather than folded into one "used in" string, so
            // the list stays scannable when it grows: the two ticks are what
            // tells apart an expense category from a catalogue one at a glance.
            StringColumn::new('usedForPurchases')
                ->label(CategoryUsage::Purchase->translationKey())
                ->sortable(false)
                ->filter(self::usageFilter('usedForPurchases'))
                ->formatValue(static fn (mixed $value): string => $value ? '✓' : '—'),
            StringColumn::new('usedForCatalog')
                ->label(CategoryUsage::Catalog->translationKey())
                ->sortable(false)
                ->filter(self::usageFilter('usedForCatalog'))
                ->formatValue(static fn (mixed $value): string => $value ? '✓' : '—'),
        ];
    }

    /**
     * @return EditAction[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            EditAction::new('_categories_edit', ['id' => 'id']),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        yield BatchAction::new('Delete')
            ->icon('trash')
            ->color('danger')
            ->confirmMessage('Are you sure you want to delete the selected categories?')
            ->action(static function (CategoryRepository $repository, array $selectedItems): void {
                foreach ($selectedItems as $id) {
                    $category = $repository->find($id);

                    if ($category instanceof Category) {
                        $repository->delete($category);
                    }
                }
            });
    }

    private static function usageFilter(string $field): ChoiceFilter
    {
        return ChoiceFilter::new($field, ['category.filter.yes' => '1', 'category.filter.no' => '0']);
    }

    public function getCreateRoute(): string
    {
        return '_categories_add';
    }

    #[Override]
    public function getCreateLabel(): TranslatableMessage
    {
        return new TranslatableMessage('category.grid.create');
    }

    #[Override]
    public function getEmptyTitle(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.category.title');
    }

    #[Override]
    public function getEmptyDescription(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.category.description');
    }
}
