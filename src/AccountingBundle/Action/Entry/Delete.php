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

namespace Augias\AccountingBundle\Action\Entry;

use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\LedgerEntrySource;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Removes a hand-written entry from an open period.
 *
 * Only ever a manual one. An automatic entry mirrors a payment that still
 * exists, so deleting it would leave the book disagreeing with the record it
 * was drawn from — and the next flush of that payment would write it back
 * anyway. A sealed entry cannot be removed at all; the Doctrine listener
 * refuses it, and this refuses it first so the user gets an explanation rather
 * than an error page.
 */
final readonly class Delete
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private RouterInterface $router,
    ) {
    }

    public function __invoke(LedgerEntry $entry, Request $request, Session $session): Response
    {
        $book = $entry->getBook()->value;
        $token = $request->request->get('_token');

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken('delete' . $entry->getId(), $token))) {
            $session->getFlashBag()->add('danger', 'accounting.entry.flash.invalid_token');

            return new RedirectResponse($this->router->generate('_accounting_book', ['book' => $book]));
        }

        if ($entry->isLocked()) {
            $session->getFlashBag()->add('warning', 'accounting.entry.flash.locked');

            return new RedirectResponse($this->router->generate('_accounting_book', ['book' => $book]));
        }

        if ($entry->getSource() !== LedgerEntrySource::Manual) {
            $session->getFlashBag()->add('warning', 'accounting.entry.flash.automatic');

            return new RedirectResponse($this->router->generate('_accounting_book', ['book' => $book]));
        }

        $entityManager = $this->doctrine->getManager();
        $entityManager->remove($entry);
        $entityManager->flush();

        $session->getFlashBag()->add('success', 'accounting.entry.flash.deleted');

        return new RedirectResponse($this->router->generate('_accounting_book', ['book' => $book]));
    }
}
