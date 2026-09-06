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

namespace Augias\CoreBundle\Listener;

use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Entity\Contact;
use Augias\CoreBundle\Enum\CustomFieldTarget;
use Augias\CoreBundle\Repository\CustomFieldValueRepository;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Entity\RecurringInvoice;
use Augias\QuoteBundle\Entity\Quote;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

/**
 * @see \Augias\CoreBundle\Tests\Listener\CustomFieldValueCleanupListenerTest
 */
#[AsDoctrineListener(event: Events::preRemove)]
final readonly class CustomFieldValueCleanupListener
{
    public function __construct(
        private CustomFieldValueRepository $values,
    ) {
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        $entity = $event->getObject();

        $target = match (true) {
            $entity instanceof Client => CustomFieldTarget::CLIENT,
            $entity instanceof Contact => CustomFieldTarget::CONTACT,
            $entity instanceof Invoice, $entity instanceof RecurringInvoice => CustomFieldTarget::INVOICE,
            $entity instanceof Quote => CustomFieldTarget::QUOTE,
            default => null,
        };

        if ($target === null || $entity->getId() === null) {
            return;
        }

        $this->values->deleteForRecord($target, $entity->getId());
    }
}
