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

namespace Augias\CoreBundle\Telemetry\Listener;

use Augias\CoreBundle\Telemetry\Telemetry;
use Augias\CoreBundle\Telemetry\TelemetryEvent;
use Augias\InvoiceBundle\Entity\RecurringInvoice;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, entity: RecurringInvoice::class)]
final readonly class RecurringInvoiceCreatedTelemetryListener
{
    public function __construct(
        private Telemetry $telemetry,
    ) {
    }

    public function postPersist(RecurringInvoice $recurringInvoice): void
    {
        $this->telemetry->event(TelemetryEvent::RecurringInvoiceCreated);
    }
}
