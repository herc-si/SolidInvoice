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

namespace Augias\PaymentBundle\Listener;

use Augias\NotificationBundle\Notification\NotificationManager;
use Augias\PaymentBundle\Event\PaymentCompleteEvent;
use Augias\PaymentBundle\Event\PaymentEvents;
use Augias\PaymentBundle\Notification\PaymentReceivedNotification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentReceivedListener implements EventSubscriberInterface
{
    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PaymentEvents::PAYMENT_COMPLETE => 'onPaymentCapture',
        ];
    }

    public function __construct(
        private readonly NotificationManager $notification
    ) {
    }

    public function onPaymentCapture(PaymentCompleteEvent $event): void
    {
        $this->notification->sendNotification(new PaymentReceivedNotification(['payment' => $event->getPayment()]));
    }
}
