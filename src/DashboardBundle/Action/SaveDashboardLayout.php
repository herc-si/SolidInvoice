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

use Augias\DashboardBundle\Layout\DashboardLayout;
use Augias\DashboardBundle\Layout\DashboardLayoutManager;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Stores the arrangement the user just dragged into place.
 *
 * @see \Augias\DashboardBundle\Tests\Action\SaveDashboardLayoutTest
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final readonly class SaveDashboardLayout
{
    public const string CSRF_TOKEN_ID = 'dashboard_layout';

    public function __construct(
        private DashboardLayoutManager $layoutManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, (string) $request->headers->get('X-CSRF-Token')))) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse(['error' => 'Malformed payload.'], Response::HTTP_BAD_REQUEST);
        }

        if (! is_array($payload)) {
            return new JsonResponse(['error' => 'Malformed payload.'], Response::HTTP_BAD_REQUEST);
        }

        // Everything past this point is hostile input. fromArray() drops what it
        // cannot read and the manager reconciles the rest against the registry,
        // so no field here is trusted to name a real widget or a real zone.
        $this->layoutManager->save(DashboardLayout::fromArray($payload));

        return new JsonResponse(['status' => 'ok']);
    }
}
