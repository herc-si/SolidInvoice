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

namespace Augias\UserBundle\DataGrid;

use Augias\DataGridBundle\Attributes\AsDataGrid;
use Augias\DataGridBundle\Grid;
use Augias\DataGridBundle\GridBuilder\Column\Column;
use Augias\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use Augias\DataGridBundle\GridBuilder\Column\StatusColumn;
use Augias\DataGridBundle\GridBuilder\Column\StringColumn;
use Augias\UserBundle\Entity\User;
use Override;

#[AsDataGrid(name: 'users_list', title: 'Users')]
final class UserGrid extends Grid
{
    public function entityFQCN(): string
    {
        return User::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('email')
                ->label('user.grid.email'),
            StringColumn::new('mobile')
                ->label('user.grid.mobile')
                ->formatValue(fn ($value) => $value ?: '—'),
            RelativeDateColumn::new('created')
                ->label('user.grid.joined'),
            RelativeDateColumn::new('lastLogin')
                ->label('user.grid.last_login')
                ->formatValue(fn ($value) => $value ?: 'Never'),
            StatusColumn::new('enabled')
                ->label('user.grid.status')
                ->formatValue(fn ($value) => $value ? 'active' : 'disabled')
                ->statusMap([
                    'active' => 'success',
                    'disabled' => 'danger',
                ]),
        ];
    }
}
