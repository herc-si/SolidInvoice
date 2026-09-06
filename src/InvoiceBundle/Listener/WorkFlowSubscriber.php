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

namespace Augias\InvoiceBundle\Listener;

use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Entity\RecurringInvoice;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Enum\RecurringInvoiceStatus;
use Augias\InvoiceBundle\Model\Graph;
use Augias\InvoiceBundle\Notification\InvoiceStatusNotification;
use Augias\NotificationBundle\Notification\NotificationManager;
use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

/**
 * @see \Augias\InvoiceBundle\Tests\Listener\WorkFlowSubscriberTest
 */
class WorkFlowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly NotificationManager $notification
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.invoice.entered' => 'onWorkflowTransitionApplied',
            'workflow.recurring_invoice.entered' => 'onWorkflowTransitionApplied',
        ];
    }

    /**
     * @template TSubject of object
     *
     * @param Event<TSubject> $event
     */
    public function onWorkflowTransitionApplied(Event $event): void
    {
        /** @var Invoice|RecurringInvoice $invoice */
        $invoice = $event->getSubject();

        if (($transition = $event->getTransition()) instanceof Transition) {
            if (Graph::TRANSITION_PAY === $transition->getName()) {
                $invoice->setPaidDate(CarbonImmutable::now());
            }

            if (Graph::TRANSITION_ARCHIVE === $transition->getName()) {
                $invoice->archive();
            }
        }

        $em = $this->registry->getManager();
        $em->persist($invoice);
        $em->flush();

        $isNew = match (true) {
            $invoice instanceof Invoice => InvoiceStatus::New === $invoice->getStatus(),
            $invoice instanceof RecurringInvoice => RecurringInvoiceStatus::New === $invoice->getStatus(),
        };

        if (! $isNew) {
            $this->notification->sendNotification(new InvoiceStatusNotification(['invoice' => $invoice]));
        }
    }
}
