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

namespace Augias\InvoiceBundle\Tests\Notification;

use Augias\ClientBundle\Entity\Client;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Entity\ReminderType;
use Augias\InvoiceBundle\Notification\InvoiceReminderNotification;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceReminderNotification::class)]
final class InvoiceReminderNotificationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetSubjectForPreDueReminder(): void
    {
        $invoice = $this->createInvoice('INV-123');

        $notification = new InvoiceReminderNotification([
            'invoice' => $invoice,
            'reminder_type' => ReminderType::PreDue,
            'days_until_due' => 3,
        ]);

        $subject = $notification->getSubject();

        self::assertSame('Upcoming Payment Due: Invoice INV-123', $subject);
    }

    public function testGetSubjectForOverdue1DayReminder(): void
    {
        $invoice = $this->createInvoice('INV-456');

        $notification = new InvoiceReminderNotification([
            'invoice' => $invoice,
            'reminder_type' => ReminderType::Overdue1,
        ]);

        $subject = $notification->getSubject();

        self::assertSame('Payment Reminder: Invoice INV-456', $subject);
    }

    public function testGetSubjectForOverdue7DayReminder(): void
    {
        $invoice = $this->createInvoice('INV-789');

        $notification = new InvoiceReminderNotification([
            'invoice' => $invoice,
            'reminder_type' => ReminderType::Overdue7,
        ]);

        $subject = $notification->getSubject();

        self::assertSame('Payment Overdue: Invoice INV-789', $subject);
    }

    public function testGetSubjectForOverdue14DayReminder(): void
    {
        $invoice = $this->createInvoice('INV-999');

        $notification = new InvoiceReminderNotification([
            'invoice' => $invoice,
            'reminder_type' => ReminderType::Overdue14,
        ]);

        $subject = $notification->getSubject();

        self::assertSame('URGENT: Invoice INV-999 - Immediate Action Required', $subject);
    }

    public function testGetParametersIncludesInvoiceAndReminderType(): void
    {
        $invoice = $this->createInvoice('INV-001');

        $notification = new InvoiceReminderNotification([
            'invoice' => $invoice,
            'reminder_type' => ReminderType::PreDue,
            'days_until_due' => 3,
        ]);

        $parameters = $notification->getParameters();

        self::assertArrayHasKey('invoice', $parameters);
        self::assertSame($invoice, $parameters['invoice']);
        self::assertArrayHasKey('reminder_type', $parameters);
        self::assertSame(ReminderType::PreDue, $parameters['reminder_type']);
        self::assertArrayHasKey('days_until_due', $parameters);
        self::assertSame(3, $parameters['days_until_due']);
    }

    private function createInvoice(string $invoiceId): Invoice
    {
        $client = M::mock(Client::class);

        $invoice = M::mock(Invoice::class);
        $invoice->shouldReceive('getInvoiceId')->andReturn($invoiceId);
        $invoice->shouldReceive('getClient')->andReturn($client);
        $invoice->shouldReceive('getUsers')->andReturn(collect([]));

        return $invoice;
    }
}
