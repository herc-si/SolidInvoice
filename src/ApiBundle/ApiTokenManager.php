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

namespace Augias\ApiBundle;

use Augias\ApiBundle\Security\ApiTokenHasher;
use Augias\UserBundle\Entity\ApiToken;
use Augias\UserBundle\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @see \Augias\ApiBundle\Tests\ApiTokenManagerTest
 */
class ApiTokenManager
{
    final public const int TOKEN_LENGTH = 32;

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly ApiTokenHasher $hasher,
    ) {
    }

    /**
     * Returns an existing token entity by name when present, otherwise creates
     * a new one. When an existing token is returned, the plaintext is not
     * available (it is never persisted) and {@see GeneratedApiToken::$plaintext}
     * is an empty string.
     */
    public function getOrCreate(User $user, string $name, ?string $description = null): GeneratedApiToken
    {
        foreach ($user->getApiTokens() as $token) {
            if ($token->getName() === $name) {
                return new GeneratedApiToken($token, '');
            }
        }

        return $this->create($user, $name, $description);
    }

    public function create(User $user, string $name, ?string $description = null): GeneratedApiToken
    {
        $plaintext = $this->generateToken();

        $apiToken = new ApiToken();
        $apiToken->setToken($this->hasher->hash($plaintext));
        $apiToken->setUser($user);
        $apiToken->setName($name);
        $apiToken->setDescription($description);

        $entityManager = $this->registry->getManager();
        $entityManager->persist($apiToken);
        $entityManager->flush();

        return new GeneratedApiToken($apiToken, $plaintext);
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }
}
