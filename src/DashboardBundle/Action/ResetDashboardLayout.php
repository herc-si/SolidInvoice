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

namespace Augias\DashboardBundle\Action;

use Augias\DashboardBundle\Layout\DashboardLayoutManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Throws the user's arrangement away and goes back to the shipped defaults.
 *
 * A form POST with a redirect rather than the fetch the save action uses: this
 * is the escape hatch for a dashboard the user has made a mess of, so it has to
 * work even when the JavaScript that made the mess is the thing that is broken.
 *
 * @see \Augias\DashboardBundle\Tests\Action\ResetDashboardLayoutTest
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final readonly class ResetDashboardLayout
{
    public const string CSRF_TOKEN_ID = 'dashboard_layout_reset';

    public function __construct(
        private DashboardLayoutManager $layoutManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        if ($this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, (string) $request->request->get('_token')))) {
            $this->layoutManager->reset();

            $session = $this->requestStack->getSession();

            if (method_exists($session, 'getFlashBag')) {
                $session->getFlashBag()->add('success', 'dashboard.layout.reset_message');
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('_dashboard'));
    }
}
