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

namespace Augias\UserBundle\Twig\Components;

use Augias\UserBundle\Entity\ApiToken;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Repository\ApiTokenRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * @see \Augias\UserBundle\Tests\Twig\Components\ApiTokenHistoryModalTest
 */
#[AsLiveComponent]
final class ApiTokenHistoryModal
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $tokenId = null;

    public function __construct(
        private readonly ApiTokenRepository $tokenRepository,
        private readonly Security $security,
    ) {
    }

    #[LiveAction]
    public function open(string $tokenId): void
    {
        $this->tokenId = $tokenId;
    }

    #[LiveAction]
    public function close(): void
    {
        $this->tokenId = null;
    }

    #[ExposeInTemplate]
    public function getToken(): ?ApiToken
    {
        if (null === $this->tokenId) {
            return null;
        }

        $token = $this->tokenRepository->find(Ulid::fromString($this->tokenId));

        if (null === $token) {
            return null;
        }

        // Verify token belongs to current user
        $currentUser = $this->security->getUser();
        if (! $currentUser instanceof User) {
            return null;
        }

        assert($token->getUser() instanceof User);
        if ($token->getUser()->getId() !== $currentUser->getId()) {
            return null;
        }

        return $token;
    }
}
