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

namespace Augias\InvoiceBundle\Listener\Mailer;

use Augias\InvoiceBundle\Email\InvoiceEmail;
use Augias\SettingsBundle\SystemConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;

class InvoiceSubjectListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemConfig $config
    ) {
    }

    public function __invoke(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if ($message instanceof InvoiceEmail && null === $message->getSubject()) {
            $message->subject(\str_replace('{id}', (string) $message->getInvoice()->getInvoiceId(), $this->config->get('invoice/email_subject')));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
