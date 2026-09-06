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

namespace Augias\ApiBundle\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Augias\UserBundle\Entity\ApiToken;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Repository\ApiTokenRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<ApiToken> */
final readonly class ApiTokenItemProvider implements ProviderInterface
{
    public function __construct(
        private ApiTokenRepository $apiTokenRepository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ApiToken
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            throw new AccessDeniedHttpException('You must be authenticated to access API tokens.');
        }

        $token = $this->apiTokenRepository->findOneBy([
            'id' => $uriVariables['id'],
            'user' => $user,
        ]);

        if (! $token instanceof ApiToken) {
            throw new NotFoundHttpException('API token not found.');
        }

        return $token;
    }
}
