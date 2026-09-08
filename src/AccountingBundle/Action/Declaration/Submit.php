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

namespace Augias\AccountingBundle\Action\Declaration;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Service\DeclarationBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use function trim;

/**
 * Records that the user filed the declaration, with the reference they were
 * given.
 *
 * Augias submits nothing to anybody: the figures are copied onto the
 * authority's own site by hand, and this is the user telling the application
 * that they did. It is still a one-way step, because from here the declaration
 * stops being recomputed and becomes the record of what was actually sent.
 */
final readonly class Submit
{
    public function __construct(
        private DeclarationBuilder $builder,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private RouterInterface $router,
    ) {
    }

    public function __invoke(AccountingPeriod $period, Request $request, Session $session): Response
    {
        $redirect = new RedirectResponse(
            $this->router->generate('_accounting_declaration_view', ['id' => $period->getId()]),
        );

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken('submit' . $period->getId(), $request->request->get('_token')))) {
            $session->getFlashBag()->add('danger', 'accounting.entry.flash.invalid_token');

            return $redirect;
        }

        $declaration = $this->builder->forPeriod($period);

        if ($declaration->isSubmitted()) {
            $session->getFlashBag()->add('warning', 'accounting.declaration.flash.already_submitted');

            return $redirect;
        }

        $reference = trim((string) $request->request->get('reference'));
        $notes = trim((string) $request->request->get('notes'));

        $this->builder->markSubmitted(
            $declaration,
            '' === $reference ? null : $reference,
            '' === $notes ? null : $notes,
        );

        $session->getFlashBag()->add('success', 'accounting.declaration.flash.submitted');

        return $redirect;
    }
}
