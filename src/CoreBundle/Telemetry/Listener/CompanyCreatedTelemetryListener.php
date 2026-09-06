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

use Augias\CoreBundle\Event\CompanyCreatedEvent;
use Augias\CoreBundle\Telemetry\Telemetry;
use Augias\CoreBundle\Telemetry\TelemetryEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(CompanyCreatedEvent::class)]
final readonly class CompanyCreatedTelemetryListener
{
    public function __construct(
        private Telemetry $telemetry,
    ) {
    }

    public function __invoke(CompanyCreatedEvent $event): void
    {
        $this->telemetry->event(TelemetryEvent::CompanyCreated);
    }
}
