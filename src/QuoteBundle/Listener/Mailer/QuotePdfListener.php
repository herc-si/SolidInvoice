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

namespace Augias\QuoteBundle\Listener\Mailer;

use Augias\CoreBundle\Pdf\Generator;
use Augias\CoreBundle\Templates\BillingTemplateChannel;
use Augias\CoreBundle\Templates\BillingTemplateResolver;
use Augias\QuoteBundle\Email\QuoteEmail;
use Mpdf\MpdfException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @see \Augias\QuoteBundle\Tests\Listener\Mailer\QuotePdfListenerTest
 */
readonly class QuotePdfListener implements EventSubscriberInterface
{
    public function __construct(
        private Generator $generator,
        private Environment $twig,
        private BillingTemplateResolver $templateResolver,
    ) {
    }

    /**
     * @throws MpdfException|LoaderError|RuntimeError|SyntaxError
     */
    public function __invoke(MessageEvent $event): void
    {
        /** @var QuoteEmail $message */
        $message = $event->getMessage();

        if ($message instanceof QuoteEmail && $this->generator->canPrintPdf()) {
            $content = $this->generator->generate(
                $this->twig->render($this->templateResolver->resolve($message->getQuote(), BillingTemplateChannel::Pdf), ['quote' => $message->getQuote()])
            );

            $message->attach($content, sprintf('quote_%s.pdf', $message->getQuote()->getQuoteId()), 'application/pdf');
        }
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
