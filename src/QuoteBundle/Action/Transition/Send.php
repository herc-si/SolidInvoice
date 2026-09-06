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

namespace Augias\QuoteBundle\Action\Transition;

use Augias\CoreBundle\Contracts\EmailVerificationGateInterface;
use Augias\CoreBundle\Response\FlashResponse;
use Augias\QuoteBundle\Entity\Quote;
use Augias\QuoteBundle\Exception\InvalidTransitionException;
use Augias\QuoteBundle\Mailer\QuoteMailer;
use Generator;
use JsonException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

final readonly class Send
{
    public function __construct(
        private QuoteMailer $mailer,
        private RouterInterface $router,
        private EmailVerificationGateInterface $emailVerificationGate,
    ) {
    }

    public function __invoke(Request $request, Quote $quote): RedirectResponse
    {
        $route = $this->router->generate('_quotes_view', ['id' => $quote->getId()]);

        if ($this->emailVerificationGate->isGated()) {
            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield FlashResponse::FLASH_ERROR => 'email_verification.flash.send_quote';
                }
            };
        }

        try {
            $this->mailer->send($quote);
        } catch (JsonException | InvalidTransitionException | TransportExceptionInterface $e) {
            return new class($route, $e->getMessage()) extends RedirectResponse implements FlashResponse {
                public function __construct(
                    string $route,
                    private readonly string $message
                ) {
                    parent::__construct($route);
                }

                public function getFlash(): Generator
                {
                    yield self::FLASH_ERROR => $this->message;
                }
            };
        }

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'quote.transition.action.sent';
            }
        };
    }
}
