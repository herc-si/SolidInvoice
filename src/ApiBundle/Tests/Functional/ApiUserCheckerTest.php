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

namespace Augias\ApiBundle\Tests\Functional;

use Augias\ApiBundle\ApiTokenManager;
use Augias\ApiBundle\Test\ApiTestCase;
use Augias\ClientBundle\Entity\Client;
use Augias\UserBundle\Test\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the global disabled-account block is enforced at API authentication
 * via the firewall's {@see \Augias\UserBundle\Security\VerifiedUserChecker}.
 */
#[Group('functional')]
final class ApiUserCheckerTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Client::class;
    }

    public function testDisabledUserIsBlockedFromApi(): void
    {
        $disabled = UserFactory::createOne(['companies' => [$this->company], 'enabled' => false]);

        $manager = self::getContainer()->get(ApiTokenManager::class);
        self::assertInstanceOf(ApiTokenManager::class, $manager);
        $token = $manager->create($disabled, 'Disabled User Token');

        self::$client->request('GET', '/api/clients', [
            'headers' => [
                'X-API-TOKEN' => $token->plaintext,
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
