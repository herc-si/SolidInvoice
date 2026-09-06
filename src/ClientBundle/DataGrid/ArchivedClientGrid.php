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

namespace Augias\ClientBundle\DataGrid;

use Augias\ClientBundle\Repository\ClientRepository;
use Augias\CoreBundle\Doctrine\Filter\ArchivableFilter;
use Augias\DataGridBundle\Attributes\AsDataGrid;
use Augias\DataGridBundle\GridBuilder\Batch\BatchAction;
use Augias\DataGridBundle\GridBuilder\Query;
use Doctrine\ORM\EntityManagerInterface;
use Override;

#[AsDataGrid(name: 'archived_client_grid', title: 'Archived Clients')]
final class ArchivedClientGrid extends BaseClientGrid
{
    #[Override]
    public function batchActions(): iterable
    {
        yield from parent::batchActions();

        yield BatchAction::new('Activate')
            ->icon('refresh')
            ->color('success')
            ->action(static function (ClientRepository $repository, array $selectedItems): void {
                $repository->restoreClients($selectedItems);
            });
    }

    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        return ArchivableFilter::disableForGrid($entityManager, $query);
    }
}
