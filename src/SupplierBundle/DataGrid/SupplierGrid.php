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

namespace SolidInvoice\SupplierBundle\DataGrid;

use Doctrine\ORM\EntityManagerInterface;
use Override;
use SolidInvoice\ClientBundle\DataGrid\BaseClientGrid;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * A filtered view of {@see \SolidInvoice\ClientBundle\Entity\Client} — every
 * row with {@see \SolidInvoice\ClientBundle\Entity\Client::isSupplier()} true
 * — rather than a separate table: a party is a client, a supplier, or both,
 * on the same record.
 *
 * @see \SolidInvoice\SupplierBundle\Tests\DataGrid\SupplierGridTest
 */
#[AsDataGrid(name: 'supplier_grid', title: 'Suppliers')]
final class SupplierGrid extends BaseClientGrid
{
    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query = parent::query($entityManager, $query);

        $query->getQueryBuilder()
            ->andWhere(ORMSource::ALIAS . '.isSupplier = true');

        return $query;
    }

    /**
     * @return list<ViewAction|EditAction>
     */
    #[Override]
    public function actions(): array
    {
        return [
            ViewAction::new('_suppliers_view', ['id' => 'id']),
            EditAction::new('_suppliers_edit', ['id' => 'id']),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        // Not a hard delete: a supplier may also be a client (isClient true),
        // so this grid only ever removes the supplier role, never the party
        // itself — mirrors SupplierBundle\Action\Delete's single-record logic.
        yield BatchAction::new('Remove supplier role')
            ->icon('trash')
            ->color('warning')
            ->action(static function (ClientRepository $repository, array $selectedItems): void {
                $repository->removeSupplierRole($selectedItems);
            });
    }

    public function getCreateRoute(): ?string
    {
        return '_suppliers_add';
    }

    #[Override]
    public function getCreateLabel(): ?TranslatableMessage
    {
        return new TranslatableMessage('supplier.grid.create');
    }

    #[Override]
    public function getEmptyTitle(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.supplier.title');
    }

    #[Override]
    public function getEmptyDescription(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.empty.supplier.description');
    }
}
