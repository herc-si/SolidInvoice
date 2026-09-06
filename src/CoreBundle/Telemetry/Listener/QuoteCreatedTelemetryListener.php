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
use Augias\QuoteBundle\Entity\Quote;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, entity: Quote::class)]
final readonly class QuoteCreatedTelemetryListener
{
    public function __construct(
        private Telemetry $telemetry,
    ) {
    }

    public function postPersist(Quote $quote): void
    {
        $this->telemetry->event(TelemetryEvent::QuoteCreated);
    }
}
