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

use Augias\DataGridBundle\Attributes\AsDataGrid;
use Augias\DataGridBundle\GridBuilder\Batch\BatchAction;
use Augias\DataGridBundle\GridBuilder\Query;
use Augias\InvoiceBundle\Enum\RecurringInvoiceStatus;
use Augias\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;

#[AsDataGrid(name: 'completed_recurring_invoice_grid', title: 'Completed Recurring Invoices')]
final class CompletedRecurringInvoiceGrid extends BaseRecurringInvoiceGrid
{
    // Completed invoices don't need the nextRunDate column
    // so we use the parent implementation which includes it

    #[Override]
    public function batchActions(): iterable
    {
        yield from parent::batchActions();

        yield BatchAction::new('Reactivate')
            ->icon('refresh')
            ->color('success')
            ->action(static function (RecurringInvoiceRepository $repository, EntityManagerInterface $em, array $selectedItems): void {
                $invoices = $repository->findBy(['id' => $selectedItems]);
                foreach ($invoices as $invoice) {
                    $invoice->setStatus(RecurringInvoiceStatus::Active);
                    $em->persist($invoice);
                }

                $em->flush();
            });
    }

    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $queryBuilder = $query->getQueryBuilder();
        $queryBuilder->andWhere(sprintf('%s.status = :completedStatus', $query->getRootAlias()))
            ->setParameter('completedStatus', RecurringInvoiceStatus::Complete->value);

        return parent::query($entityManager, $query);
    }

    #[Override]
    public function getCreateRoute(): ?string
    {
        return null;
    }
}
