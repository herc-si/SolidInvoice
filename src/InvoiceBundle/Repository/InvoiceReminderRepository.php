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

namespace Augias\InvoiceBundle\Repository;

use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Entity\InvoiceReminder;
use Augias\InvoiceBundle\Entity\ReminderType;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<InvoiceReminder>
 * @see \Augias\InvoiceBundle\Tests\Repository\InvoiceReminderRepositoryTest
 */
class InvoiceReminderRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceReminder::class);
    }

    /**
     * Check if a specific reminder type has been sent for an invoice.
     */
    public function hasReminderBeenSent(Invoice $invoice, ReminderType $reminderType): bool
    {
        return null !== $this->findOneBy([
            'invoice' => $invoice,
            'reminderType' => $reminderType,
        ]);
    }

    /**
     * Get all reminders sent for an invoice.
     *
     * @return InvoiceReminder[]
     */
    public function getReminderHistory(Invoice $invoice): array
    {
        return $this->findBy(
            ['invoice' => $invoice],
            ['sentAt' => 'ASC']
        );
    }
}
