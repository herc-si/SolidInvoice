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

use Augias\CoreBundle\Feature\NullUpgradePromptProvider;
use Augias\CoreBundle\Feature\UpgradePromptProvider;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Test\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Group;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Verifies the SaaS feature-gate short-circuits the ClientBundle Add action
 * with the upgrade banner when the `total_clients` quota is exhausted, and lets
 * the form render normally when under quota or in self-hosted mode.
 */
#[Group('functional')]
final class ClientCreateQuotaGateTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    private const string GATED_HEADLINE = 'This feature requires an upgrade';

    public function testAtLimitRendersUpgradeBanner(): void
    {
        $client = $this->bootClient($this->buildFeatureGate(['total_clients' => false]));

        $client->request(Request::METHOD_GET, '/clients/add');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
    }

    public function testUnderQuotaBypassesBanner(): void
    {
        $client = $this->bootClient($this->buildFeatureGate(['total_clients' => true]));

        $client->request(Request::METHOD_GET, '/clients/add');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
    }

    public function testSelfHostedBypassesBanner(): void
    {
        $client = $this->bootClient();

        $container = self::getContainer();

        $providerId = 'test.' . UpgradePromptProvider::class;
        self::assertTrue($container->has($providerId));

        if (($_ENV['AUGIAS_PLATFORM'] ?? $_SERVER['AUGIAS_PLATFORM'] ?? null) !== 'saas') {
            self::assertInstanceOf(NullUpgradePromptProvider::class, $container->get($providerId));
        }

        $client->request(Request::METHOD_GET, '/clients/add');

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString(self::GATED_HEADLINE, (string) $client->getResponse()->getContent());
    }

    /**
     * @param array<string, bool> $overrides
     */
    private function buildFeatureGate(array $overrides): FeatureGate
    {
        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')
            ->willReturnCallback(static fn (string $key): bool => $overrides[$key] ?? true);
        $featureGate->method('canUse')
            ->willReturnCallback(static fn (string $key): bool => $overrides[$key] ?? true);

        return $featureGate;
    }

    private function bootClient(?FeatureGate $featureGate = null): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        if ($featureGate instanceof FeatureGate) {
            self::getContainer()->set(FeatureGate::class, $featureGate);
        }

        $user = UserFactory::createOne(['companies' => [$this->company]]);
        self::assertInstanceOf(User::class, $user);
        $client->loginUser($user);

        return $client;
    }
}
