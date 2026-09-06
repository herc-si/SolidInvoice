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

namespace Augias\ApiBundle\Security\Provider;

use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Repository\ApiTokenRepository;
use Augias\UserBundle\Repository\UserRepositoryInterface;
use SensitiveParameter;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 */
class ApiTokenUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly ApiTokenRepository $tokenRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function getUsernameForToken(#[SensitiveParameter] string $token): ?string
    {
        return $this->tokenRepository->getUsernameForToken($token);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        // this is used for storing authentication in the session
        // but the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneBy(['email' => $identifier]);

        if (! $user) {
            throw new UserNotFoundException();
        }

        return $user;
    }
}
