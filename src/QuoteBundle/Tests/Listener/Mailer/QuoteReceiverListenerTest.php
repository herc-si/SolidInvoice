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

namespace Augias\QuoteBundle\Tests\Listener\Mailer;

use Augias\ClientBundle\Entity\Contact;
use Augias\QuoteBundle\Email\QuoteEmail;
use Augias\QuoteBundle\Entity\Quote;
use Augias\QuoteBundle\Listener\Mailer\QuoteReceiverListener;
use Augias\SettingsBundle\SystemConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;

final class QuoteReceiverListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testWithoutBcc(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('quote/bcc_address')
            ->andReturnNull();

        $listener = new QuoteReceiverListener($config);
        $quote = new Quote();
        $quote->addUser(new Contact()->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $quote->addUser(new Contact()->setEmail('another@example.com')->setFirstName('Another'));

        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals([new Address('test@example.com', 'Test User'), new Address('another@example.com', 'Another')], $message->getTo());
        self::assertSame([], $message->getBcc());
    }

    public function testWithBcc(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('quote/bcc_address')
            ->andReturn('bcc@example.com');

        $listener = new QuoteReceiverListener($config);
        $quote = new Quote();
        $quote->addUser(new Contact()->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $quote->addUser(new Contact()->setEmail('another@example.com')->setFirstName('Another'));

        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals([new Address('test@example.com', 'Test User'), new Address('another@example.com', 'Another')], $message->getTo());
        self::assertEquals([new Address('bcc@example.com')], $message->getBcc());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(QuoteReceiverListener::getSubscribedEvents()));
    }
}
