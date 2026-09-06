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

namespace Augias\CoreBundle\Tests\Listener;

use Augias\CoreBundle\Listener\EmailFromListener;
use Augias\SettingsBundle\SystemConfig;
use Augias\UserBundle\Entity\User;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class EmailFromListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testWithFromAddressConfigured(): void
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig->shouldReceive('get')
            ->with('email/from_address')
            ->andReturn('info@example.com');

        $systemConfig->shouldReceive('get')
            ->with('email/from_name')
            ->andReturn('Augias');

        $tokenStorage = M::mock(TokenStorageInterface::class);

        $tokenStorage->shouldNotReceive('getToken');

        $listener = new EmailFromListener($systemConfig, $tokenStorage);

        $message = new TemplatedEmail();
        $envelope = Envelope::create($message);
        $listener(new MessageEvent($message, $envelope, 'smtp'));

        self::assertEquals([new Address('info@example.com', 'Augias')], $message->getFrom());
        self::assertSame('info@example.com', $envelope->getSender()->getAddress());
    }

    public function testWithoutFromAddress(): void
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig->shouldReceive('get')
            ->with('email/from_address')
            ->andReturn(null);

        $token = M::mock(TokenInterface::class);

        $user = new User();
        $user->setEmail('test@example.com');

        $token->shouldReceive('getUser')
            ->once()
            ->withNoArgs()
            ->andReturn($user);

        $tokenStorage = M::mock(TokenStorageInterface::class);

        $tokenStorage->shouldReceive('getToken')
            ->once()
            ->withNoArgs()
            ->andReturn($token);

        $listener = new EmailFromListener($systemConfig, $tokenStorage);

        $message = new TemplatedEmail();
        $envelope = Envelope::create($message);
        $listener(new MessageEvent($message, $envelope, 'smtp'));

        self::assertEquals([new Address('test@example.com')], $message->getFrom());
        self::assertSame('test@example.com', $envelope->getSender()->getAddress());
    }

    public function testDoesNothingForNonEmailMessages(): void
    {
        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldNotReceive('get');

        $tokenStorage = M::mock(TokenStorageInterface::class);
        $tokenStorage->shouldNotReceive('getToken');

        $listener = new EmailFromListener($systemConfig, $tokenStorage);

        $message = new RawMessage('raw content');
        $envelope = new Envelope(new Address('sender@example.com'), [new Address('recipient@example.com')]);
        $listener(new MessageEvent($message, $envelope, 'smtp'));
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(EmailFromListener::getSubscribedEvents()));
        self::assertSame(['__invoke', -256], EmailFromListener::getSubscribedEvents()[MessageEvent::class]);
    }
}
