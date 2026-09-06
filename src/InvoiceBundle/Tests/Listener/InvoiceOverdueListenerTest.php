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

namespace Augias\InvoiceBundle\Tests\Listener;

use Augias\ClientBundle\Entity\Client;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Listener\InvoiceOverdueListener;
use Augias\InvoiceBundle\Model\Graph;
use Augias\NotificationBundle\Notification\NotificationManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(InvoiceOverdueListener::class)]
final class InvoiceOverdueListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListenerSendsNotificationToInternalUsers(): void
    {
        $client = new Client()->setName('Test Client')->setCurrencyCode('USD');

        $invoice = new Invoice();
        $invoice->setStatus(InvoiceStatus::Overdue);
        $invoice->setClient($client);
        $invoice->setInvoiceId('INV-001');

        $notificationManager = M::mock(NotificationManager::class);
        $notificationManager->shouldReceive('sendNotification')
            ->once();

        $logger = M::mock(LoggerInterface::class);

        $listener = new InvoiceOverdueListener($notificationManager, $logger);

        $event = new Event(
            $invoice,
            new Marking([InvoiceStatus::Overdue->value => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, InvoiceStatus::Pending->value, InvoiceStatus::Overdue->value),
            M::mock(WorkflowInterface::class)
        );

        $listener->onInvoiceOverdue($event);
    }

    public function testListenerHandlesNotificationFailure(): void
    {
        $client = new Client()->setName('Test Client')->setCurrencyCode('USD');

        $invoice = new Invoice();
        $invoice->setStatus(InvoiceStatus::Overdue);
        $invoice->setClient($client);
        $invoice->setInvoiceId('INV-001');

        $notificationManager = M::mock(NotificationManager::class);
        $notificationManager->shouldReceive('sendNotification')
            ->once()
            ->andThrow(new RuntimeException('Notification failed'));

        $logger = M::mock(LoggerInterface::class);
        $logger->shouldReceive('error')
            ->once()
            ->with('Failed to send overdue notification to users', M::any());

        $listener = new InvoiceOverdueListener($notificationManager, $logger);

        $event = new Event(
            $invoice,
            new Marking([InvoiceStatus::Overdue->value => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, InvoiceStatus::Pending->value, InvoiceStatus::Overdue->value),
            M::mock(WorkflowInterface::class)
        );

        $listener->onInvoiceOverdue($event);
    }

    public function testListenerIgnoresNonInvoiceSubjects(): void
    {
        $notificationManager = M::mock(NotificationManager::class);
        $notificationManager->shouldNotReceive('sendNotification');

        $logger = M::mock(LoggerInterface::class);

        $listener = new InvoiceOverdueListener($notificationManager, $logger);

        $event = new Event(
            new stdClass(), // Not an Invoice
            new Marking([InvoiceStatus::Overdue->value => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, InvoiceStatus::Pending->value, InvoiceStatus::Overdue->value),
            M::mock(WorkflowInterface::class)
        );

        $listener->onInvoiceOverdue($event);
    }
}
