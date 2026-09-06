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

namespace Augias\ClientBundle\Notification;

use Augias\NotificationBundle\Attribute\AsNotification;
use Augias\NotificationBundle\Enum\NotificationCategory;
use Augias\NotificationBundle\Notification\NotificationMessage;
use Augias\NotificationBundle\Notification\Options\Reference\TemplateReference;
use Augias\NotificationBundle\Notification\Options\Reference\TranslationReference;
use Augias\NotificationBundle\Notification\Options\Reference\UrlRouteReference;
use Augias\NotificationBundle\Notification\Options\SimpleMessageOptions;
use Override;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Twig\Environment;

#[AsNotification(
    name: ClientCreateNotification::EVENT,
    title: 'Client Created',
    description: 'When a new client is added to your account',
    icon: 'tabler:user-plus',
    category: NotificationCategory::CLIENT,
)]
class ClientCreateNotification extends NotificationMessage
{
    public const EVENT = 'client_create';

    final public const string HTML_TEMPLATE = '@AugiasClient/Email/client_create.html.twig';

    final public const string TEXT_TEMPLATE = '@AugiasClient/Email/client_create.text.twig';

    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    #[Override]
    public function getSubject(): string
    {
        return 'client.create.subject';
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

    #[Override]
    public function asChatMessage(RecipientInterface $recipient, ?string $transport = null): ChatMessage
    {
        return parent::asChatMessage($recipient, $transport)
            ->options(new SimpleMessageOptions([
                'Slack' => new SlackOptions($this->getSlackOptions()),
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function getSlackOptions(): array
    {
        $slackOptions = SlackOptions::fromNotification($this)->toArray();

        $slackOptions['blocks'][] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => new TemplateReference(self::TEXT_TEMPLATE, $this->getParameters()),
            ],
        ];

        $slackOptions['blocks'][] = [
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'style' => 'primary',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => new TranslationReference('View Client'),
                    ],
                    'url' => new UrlRouteReference('_clients_view', ['id' => $this->getParameters()['client']->getId()->toString()]),
                ],
            ],
        ];

        return $slackOptions;
    }
}
