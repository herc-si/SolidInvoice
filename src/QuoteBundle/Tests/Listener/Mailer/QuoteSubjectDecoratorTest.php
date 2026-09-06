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

use Augias\QuoteBundle\Email\QuoteEmail;
use Augias\QuoteBundle\Entity\Quote;
use Augias\QuoteBundle\Listener\Mailer\QuoteSubjectListener;
use Augias\SettingsBundle\SystemConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;

final class QuoteSubjectDecoratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListener(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('quote/email_subject')
            ->andReturn('New Quote: #{id}');

        $listener = new QuoteSubjectListener($config);
        $quote = new Quote();
        $quote->setQuoteId('123');

        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertSame('New Quote: #123', $message->getSubject());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(QuoteSubjectListener::getSubscribedEvents()));
    }
}
