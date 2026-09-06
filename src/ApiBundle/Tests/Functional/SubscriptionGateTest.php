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
use Augias\ApiBundle\GeneratedApiToken;
use Augias\ApiBundle\Security\ApiTokenAuthenticator;
use Augias\ApiBundle\Security\Attribute as ApiAttribute;
use Augias\ApiBundle\Security\Provider\ApiTokenUserProvider;
use Augias\CoreBundle\Company\CompanySelector;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\UserBundle\Repository\ApiTokenRepository;
use Augias\UserBundle\Test\Factory\UserFactory;
use Doctrine\Persistence\ManagerRegistry;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Verifies that a denied authorization decision blocks API authentication
 * with a 403 carrying the voter's reason. This exercises the
 * authorization-checker relay in ApiTokenAuthenticator without coupling the
 * test to any specific voter implementation.
 */
#[CoversClass(ApiTokenAuthenticator::class)]
#[Group('functional')]
final class SubscriptionGateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testAuthorizationGrantedAllowsRequest(): void
    {
        $token = $this->createApiToken();

        $authenticator = $this->buildAuthenticator($this->grantingAuthorizationChecker());

        $request = Request::create('/api/clients', Request::METHOD_GET, [], [], [], ['HTTP_X-API-TOKEN' => $token->plaintext]);

        $response = $authenticator->onAuthenticationSuccess(
            $request,
            M::mock(TokenInterface::class),
            'api',
        );

        self::assertNull($response, 'A granted decision should not produce a response.');
    }

    public function testAuthorizationDeniedBlocksRequestWithReason(): void
    {
        $reason = 'Your trial has ended. Activate a subscription to continue using this resource.';
        $token = $this->createApiToken();

        $authenticator = $this->buildAuthenticator($this->denyingAuthorizationChecker($reason));

        $request = Request::create('/api/clients', Request::METHOD_GET, [], [], [], ['HTTP_X-API-TOKEN' => $token->plaintext]);

        $response = $authenticator->onAuthenticationSuccess(
            $request,
            M::mock(TokenInterface::class),
            'api',
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);
        self::assertSame($reason, $body['message']);
    }

    public function testAuthorizationDeniedWithoutReasonFallsBackToGenericMessage(): void
    {
        $token = $this->createApiToken();

        $authenticator = $this->buildAuthenticator($this->denyingAuthorizationChecker(null));

        $request = Request::create('/api/clients', Request::METHOD_GET, [], [], [], ['HTTP_X-API-TOKEN' => $token->plaintext]);

        $response = $authenticator->onAuthenticationSuccess(
            $request,
            M::mock(TokenInterface::class),
            'api',
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);
        self::assertSame('Access denied.', $body['message']);
    }

    private function createApiToken(): GeneratedApiToken
    {
        $container = self::getContainer();

        $user = UserFactory::createOne(['companies' => [$this->company]]);

        $manager = $container->get(ApiTokenManager::class);
        self::assertInstanceOf(ApiTokenManager::class, $manager);

        $generated = $manager->getOrCreate($user, 'Subscription Gate Test');
        // Bind the token to the active company so the authenticator can switch into it.
        $generated->token->setCompany($this->company);

        $registry = $container->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $registry);
        $registry->getManager()
            ->flush();

        return $generated;
    }

    private function buildAuthenticator(AuthorizationCheckerInterface $authorizationChecker): ApiTokenAuthenticator
    {
        $container = self::getContainer();

        return new ApiTokenAuthenticator(
            $container->get(ApiTokenUserProvider::class),
            $container->get('doctrine'),
            $container->get(TranslatorInterface::class),
            $container->get(CompanySelector::class),
            $authorizationChecker,
            $container->get(ApiTokenRepository::class),
        );
    }

    private function grantingAuthorizationChecker(): AuthorizationCheckerInterface
    {
        $checker = M::mock(AuthorizationCheckerInterface::class);
        $checker
            ->shouldReceive('isGranted')
            ->with(ApiAttribute::ACCESS, null, M::any())
            ->andReturnUsing(static function (string $attribute, mixed $subject, AccessDecision $decision): bool {
                $decision->isGranted = true;

                return true;
            });

        return $checker;
    }

    private function denyingAuthorizationChecker(?string $reason): AuthorizationCheckerInterface
    {
        $checker = M::mock(AuthorizationCheckerInterface::class);
        $checker
            ->shouldReceive('isGranted')
            ->with(ApiAttribute::ACCESS, null, M::any())
            ->andReturnUsing(static function (string $attribute, mixed $subject, AccessDecision $decision) use ($reason): bool {
                $decision->isGranted = false;

                if ($reason !== null) {
                    $vote = new Vote();
                    $vote->addReason($reason);
                    $decision->votes[] = $vote;
                }

                return false;
            });

        return $checker;
    }
}
