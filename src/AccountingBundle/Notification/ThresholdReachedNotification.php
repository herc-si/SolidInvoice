<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\AccountingBundle\Notification;

use Augias\NotificationBundle\Attribute\AsNotification;
use Augias\NotificationBundle\Enum\NotificationCategory;
use Augias\NotificationBundle\Notification\NotificationMessage;
use Override;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Twig\Environment;

/**
 * Fired by {@see \Augias\AccountingBundle\Service\ThresholdMonitor} when
 * cumulative turnover passes 80% or 100% of a limit the regime sets.
 *
 * Sent by email rather than only shown on the accounting page, because the
 * consequence of missing it lands outside the application: passing a VAT
 * threshold makes a micro-entrepreneur liable for VAT part-way through the
 * year, with invoices to reissue if it is noticed late, and passing the regime
 * ceiling twice running ends the regime. Someone has to know on the day.
 */
#[AsNotification(
    name: self::EVENT,
    title: 'Turnover Threshold Reached',
    description: 'When turnover approaches or passes a limit set by your tax regime',
    icon: 'tabler:alert-triangle',
    category: NotificationCategory::ACCOUNTING,
)]
class ThresholdReachedNotification extends NotificationMessage
{
    public const EVENT = 'accounting_threshold_reached';

    final public const string HTML_TEMPLATE = '@AugiasAccounting/Email/threshold_reached.html.twig';

    final public const string TEXT_TEMPLATE = '@AugiasAccounting/Email/threshold_reached.text.twig';

    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    #[Override]
    public function getSubject(): string
    {
        return 'Turnover Threshold Reached';
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
            $email->importance(NotificationEmail::IMPORTANCE_HIGH);
        }

        return $message;
    }
}
