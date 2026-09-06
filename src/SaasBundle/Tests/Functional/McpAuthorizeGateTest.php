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

namespace Augias\SaasBundle\Tests\Functional;

use Augias\CoreBundle\Contracts\PaidSubscriptionGateInterface;
use Augias\CoreBundle\Feature\UpgradePromptProvider;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\McpBundle\Entity\OAuthClient;
use Augias\McpBundle\Repository\OAuthClientRepository;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Test\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the MCP Authorize action surfaces a friendly upgrade page when
 * the user's eligible companies are all on plans without `mcp_access`. The
 * happy path (gate enabled, valid client, full consent flow) is covered by
 * `\Augias\McpBundle\Tests\Functional\ConsentGrantTest` and the broader
 * MCP suite — here we only assert the gating edge.
 */
#[Group('functional')]
final class McpAuthorizeGateTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    public function testRendersUpgradePageWhenMcpAccessFeatureDeniedForAllCompanies(): void
    {
        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')
            ->willReturnCallback(static fn (string $key): bool => $key !== 'mcp_access');

        $upgradeProvider = $this->createStub(UpgradePromptProvider::class);
        $upgradeProvider->method('prompt')
            ->willReturnCallback(static fn (string $key): string => $key === 'mcp_access'
                ? '<div class="alert alert-warning"><strong>MCP locked</strong></div>'
                : '');
        $upgradeProvider->method('menuLabel')
            ->willReturn('Business');

        $client = $this->bootClient($featureGate, $upgradeProvider);

        $oauthClient = $this->seedOAuthClient();

        $client->request(Request::METHOD_GET, '/oauth/authorize', [
            'response_type' => 'code',
            'client_id' => $oauthClient->getIdentifier(),
            'redirect_uri' => 'http://localhost/cb',
            'scope' => 'mcp:read',
            'state' => 'xyz',
        ]);

        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        $body = (string) $client->getResponse()
            ->getContent();
        self::assertStringContainsString('MCP locked', $body);
    }

    public function testRendersUpgradePageWhenSubscriptionNotPaidEvenWithMcpAccessFeature(): void
    {
        // The plan includes mcp_access, but the tenant is not on a paid subscription
        // (e.g. a trial). MCP must still be gated behind the upgrade page.
        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')
            ->willReturn(true);

        $upgradeProvider = $this->createStub(UpgradePromptProvider::class);
        $upgradeProvider->method('prompt')
            ->willReturnCallback(static fn (string $key): string => $key === 'mcp_access'
                ? '<div class="alert alert-warning"><strong>MCP locked</strong></div>'
                : '');
        $upgradeProvider->method('menuLabel')
            ->willReturn('Business');

        $paidGate = $this->createStub(PaidSubscriptionGateInterface::class);
        $paidGate->method('isPaid')
            ->willReturn(false);

        $client = $this->bootClient($featureGate, $upgradeProvider, $paidGate);

        $oauthClient = $this->seedOAuthClient();

        $client->request(Request::METHOD_GET, '/oauth/authorize', [
            'response_type' => 'code',
            'client_id' => $oauthClient->getIdentifier(),
            'redirect_uri' => 'http://localhost/cb',
            'scope' => 'mcp:read',
            'state' => 'xyz',
        ]);

        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        self::assertStringContainsString('MCP locked', (string) $client->getResponse()->getContent());
    }

    private function seedOAuthClient(): OAuthClient
    {
        $repo = self::getContainer()->get(OAuthClientRepository::class);
        self::assertInstanceOf(OAuthClientRepository::class, $repo);

        $client = new OAuthClient();
        $client->setName('Gate test agent');
        $client->setRedirectUris(['http://localhost/cb']);
        $client->setGrantTypes(['authorization_code']);
        $client->setScopes(['mcp:read']);
        $client->setTokenEndpointAuthMethod('none');

        $repo->save($client);

        return $client;
    }

    private function bootClient(
        ?FeatureGate $featureGate = null,
        ?UpgradePromptProvider $upgradeProvider = null,
        ?PaidSubscriptionGateInterface $paidGate = null,
    ): KernelBrowser {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        if ($featureGate instanceof FeatureGate) {
            self::getContainer()->set(FeatureGate::class, $featureGate);
        }

        if ($upgradeProvider instanceof UpgradePromptProvider) {
            self::getContainer()->set(UpgradePromptProvider::class, $upgradeProvider);
        }

        if ($paidGate instanceof PaidSubscriptionGateInterface) {
            self::getContainer()->set(PaidSubscriptionGateInterface::class, $paidGate);
        }

        $user = UserFactory::createOne(['companies' => [$this->company]]);
        self::assertInstanceOf(User::class, $user);
        $client->loginUser($user);

        return $client;
    }
}
