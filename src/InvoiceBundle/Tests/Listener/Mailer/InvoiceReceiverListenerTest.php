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

namespace Augias\InvoiceBundle\Tests\Listener\Mailer;

use Augias\ClientBundle\Entity\Contact;
use Augias\InvoiceBundle\Email\InvoiceEmail;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Listener\Mailer\InvoiceReceiverListener;
use Augias\SettingsBundle\SystemConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;

final class InvoiceReceiverListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testWithoutBcc(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('invoice/bcc_address')
            ->andReturnNull();

        $listener = new InvoiceReceiverListener($config);
        $invoice = new Invoice();
        $invoice->addUser(new Contact()->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $invoice->addUser(new Contact()->setEmail('another@example.com')->setFirstName('Another'));

        $message = new InvoiceEmail($invoice);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals([new Address('test@example.com', 'Test User'), new Address('another@example.com', 'Another')], $message->getTo());
        self::assertSame([], $message->getBcc());
    }

    public function testWithBcc(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('invoice/bcc_address')
            ->andReturn('bcc@example.com');

        $listener = new InvoiceReceiverListener($config);
        $invoice = new Invoice();
        $invoice->addUser(new Contact()->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $invoice->addUser(new Contact()->setEmail('another@example.com')->setFirstName('Another'));

        $message = new InvoiceEmail($invoice);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals([new Address('test@example.com', 'Test User'), new Address('another@example.com', 'Another')], $message->getTo());
        self::assertEquals([new Address('bcc@example.com')], $message->getBcc());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(InvoiceReceiverListener::getSubscribedEvents()));
    }
}
