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

namespace Augias\PaymentBundle\Notification;

use Augias\NotificationBundle\Attribute\AsNotification;
use Augias\NotificationBundle\Enum\NotificationCategory;
use Augias\NotificationBundle\Notification\NotificationMessage;
use Override;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Twig\Environment;

#[AsNotification(
    name: self::EVENT,
    title: 'Payment Received',
    description: 'When a payment is received for an invoice',
    icon: 'tabler:cash',
    category: NotificationCategory::PAYMENT,
)]
class PaymentReceivedNotification extends NotificationMessage
{
    public const EVENT = 'payment_made';

    final public const string HTML_TEMPLATE = '@AugiasPayment/Email/payment.html.twig';

    final public const string TEXT_TEMPLATE = '@AugiasPayment/Email/payment.txt.twig';

    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    #[Override]
    public function getSubject(): string
    {
        return 'A Payment has been received';
    }

    #[Override]
    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $message = parent::asEmailMessage($recipient, $transport);

        $email = $message->getMessage();

        if ($email instanceof NotificationEmail) {
            $email->textTemplate(self::TEXT_TEMPLATE);
            $email->htmlTemplate(self::HTML_TEMPLATE);
            $email->context($this->getParameters());
        }

        return $message;
    }
}
