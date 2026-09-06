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

namespace Augias\InvoiceBundle\DataGrid;

use Augias\CoreBundle\Doctrine\Filter\ArchivableFilter;
use Augias\DataGridBundle\Attributes\AsDataGrid;
use Augias\DataGridBundle\GridBuilder\Batch\BatchAction;
use Augias\DataGridBundle\GridBuilder\Query;
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;

#[AsDataGrid(name: 'archived_invoice_grid', title: 'Archived Invoices')]
final class ArchivedInvoiceGrid extends BaseInvoiceGrid
{
    #[Override]
    public function actions(): array
    {
        return [];
    }

    #[Override]
    public function batchActions(): iterable
    {
        yield from parent::batchActions();

        yield BatchAction::new('Activate')
            ->icon('refresh')
            ->color('success')
            ->action(static function (InvoiceRepository $repository, array $selectedItems): void {
                $repository->restoreInvoices($selectedItems);
            });
    }

    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        return ArchivableFilter::disableForGrid($entityManager, parent::query($entityManager, $query));
    }
}
