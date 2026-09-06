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
use Augias\InvoiceBundle\Event\InvoiceEvent;
use Augias\InvoiceBundle\Event\InvoiceEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @see \Augias\CoreBundle\Tests\Telemetry\Listener\InvoiceCreatedTelemetryListenerTest
 */
#[AsEventListener(event: InvoiceEvents::INVOICE_POST_CREATE)]
final readonly class InvoiceCreatedTelemetryListener
{
    public function __construct(
        private Telemetry $telemetry,
    ) {
    }

    public function __invoke(InvoiceEvent $event): void
    {
        $this->telemetry->event(TelemetryEvent::InvoiceCreated);
    }
}
