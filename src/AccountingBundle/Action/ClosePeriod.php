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

namespace Augias\AccountingBundle\Action;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Exception\LedgerLockedException;
use Augias\AccountingBundle\Service\AccountingPeriodManager;
use Augias\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Seals a period.
 *
 * One-way and deliberately so, which is why it is a POST behind a token and not
 * a link: after this the period's entries are numbered, chained and frozen, and
 * a mistake found later has to be corrected by a reversing entry rather than by
 * editing what was closed.
 *
 * The refusals — already closed, or an earlier period still open — come back as
 * a message on the page rather than an error, since both are ordinary things
 * for a user to attempt.
 */
final readonly class ClosePeriod
{
    public function __construct(
        private AccountingPeriodManager $periodManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private RouterInterface $router,
        private Security $security,
    ) {
    }

    public function __invoke(AccountingPeriod $period, Request $request, Session $session): Response
    {
        $token = $request->request->get('_token');

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken('close' . $period->getId(), $token))) {
            $session->getFlashBag()->add('danger', 'accounting.entry.flash.invalid_token');

            return $this->back();
        }

        $user = $this->security->getUser();

        try {
            $this->periodManager->close($period, $user instanceof User ? $user : null);
        } catch (LedgerLockedException) {
            // The manager's message names the period and the reason, but it is
            // written for a log; the user gets the short form and the rule.
            $session->getFlashBag()->add('warning', 'accounting.period.flash.cannot_close');

            return $this->back();
        }

        $session->getFlashBag()->add('success', 'accounting.period.flash.closed');

        // To the declaration rather than back where they came from: the figures
        // are final now, and filing them is the only thing left to do with this
        // period.
        return new RedirectResponse(
            $this->router->generate('_accounting_declaration_view', ['id' => $period->getId()]),
        );
    }

    private function back(): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('_accounting_index'));
    }
}
