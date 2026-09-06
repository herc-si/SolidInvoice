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

namespace Augias\McpBundle\OAuth;

use Augias\McpBundle\Repository\McpAccessTokenRepository;
use Augias\McpBundle\Repository\McpRefreshTokenRepository;
use Augias\McpBundle\Repository\McpScopeRepository;
use Augias\McpBundle\Repository\OAuthAuthCodeRepository;
use Augias\McpBundle\Repository\OAuthClientRepository;
use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;

final readonly class ServerFactory implements ServerFactoryInterface
{
    public function __construct(
        private KeyManager $keyManager,
        private OAuthClientRepository $clientRepository,
        private McpAccessTokenRepository $accessTokenRepository,
        private McpRefreshTokenRepository $refreshTokenRepository,
        private OAuthAuthCodeRepository $authCodeRepository,
        private McpScopeRepository $scopeRepository,
        private string $accessTokenTtl = 'PT1H',
        private string $refreshTokenTtl = 'P30D',
        private string $authCodeTtl = 'PT10M',
    ) {
    }

    public function createAuthorizationServer(): AuthorizationServer
    {
        $server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $this->keyManager->getPrivateKey(),
            $this->keyManager->getEncryptionKey(),
        );

        $authCodeGrant = new StrictS256AuthCodeGrant(
            $this->authCodeRepository,
            $this->refreshTokenRepository,
            new DateInterval($this->authCodeTtl),
        );
        $authCodeGrant->setRefreshTokenTTL(new DateInterval($this->refreshTokenTtl));

        $server->enableGrantType($authCodeGrant, new DateInterval($this->accessTokenTtl));

        $refreshTokenGrant = new RefreshTokenGrant($this->refreshTokenRepository);
        $refreshTokenGrant->setRefreshTokenTTL(new DateInterval($this->refreshTokenTtl));

        $server->enableGrantType($refreshTokenGrant, new DateInterval($this->accessTokenTtl));

        return $server;
    }

    public function createResourceServer(): ResourceServer
    {
        return new ResourceServer(
            $this->accessTokenRepository,
            $this->keyManager->getPublicKey(),
        );
    }
}
