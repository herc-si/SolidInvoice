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
use Augias\InvoiceBundle\Event\InvoiceEvent;
use Augias\InvoiceBundle\Event\InvoiceEvents;
use Augias\InvoiceBundle\Listener\Mailer\InvoiceMailerListener;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;

final class InvoiceMailerListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListener(): void
    {
        $invoice = new Invoice();

        $mailer = M::spy(MailerInterface::class);
        $logger = M::spy(LoggerInterface::class);
        $requestStack = new RequestStack();

        $listener = new InvoiceMailerListener($mailer, $logger, $requestStack);

        $invoice->addUser(new Contact()->setEmail('another@example.com')->setFirstName('Another'));
        $listener->onInvoiceAccepted(new InvoiceEvent($invoice));

        $mailer->shouldHaveReceived('send')
            ->with(M::type(InvoiceEmail::class));
    }

    public function testListenerHandlesTransportException(): void
    {
        $invoice = new Invoice();

        $mailer = M::mock(MailerInterface::class);
        $mailer->shouldReceive('send')
            ->andThrow(new TransportException('Connection refused'));

        $logger = M::spy(LoggerInterface::class);

        $flashBag = new FlashBag();
        $session = $this->createStub(Session::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack([$request]);

        $listener = new InvoiceMailerListener($mailer, $logger, $requestStack);

        $invoice->addUser(new Contact()->setEmail('another@example.com')->setFirstName('Another'));

        // Should not throw - exception is caught and logged
        $listener->onInvoiceAccepted(new InvoiceEvent($invoice));

        $logger->shouldHaveReceived('error')
            ->with(M::pattern('/Failed to send invoice email/'), M::type('array'));

        self::assertSame(['invoice.email.send_failed'], $flashBag->get('error'));
    }

    public function testListenerSkipsSendingWhenNoUsers(): void
    {
        $invoice = new Invoice();

        $mailer = M::spy(MailerInterface::class);
        $logger = M::spy(LoggerInterface::class);
        $requestStack = new RequestStack();

        $listener = new InvoiceMailerListener($mailer, $logger, $requestStack);
        $listener->onInvoiceAccepted(new InvoiceEvent($invoice));

        $mailer->shouldNotHaveReceived('send');
        $logger->shouldHaveReceived('warning')
            ->with(M::pattern('/no recipients/'), M::type('array'));
    }

    public function testEvents(): void
    {
        self::assertSame([InvoiceEvents::INVOICE_POST_ACCEPT], \array_keys(InvoiceMailerListener::getSubscribedEvents()));
    }
}
