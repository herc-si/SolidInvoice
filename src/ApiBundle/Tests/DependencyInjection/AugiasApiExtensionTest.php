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

namespace Augias\ApiBundle\Tests\DependencyInjection;

use Augias\ApiBundle\ApiTokenManager;
use Augias\ApiBundle\DependencyInjection\AugiasApiExtension;
use Augias\ApiBundle\Event\Listener\AuthenticationFailHandler;
use Augias\ApiBundle\Event\Listener\AuthenticationSuccessHandler;
use Augias\ApiBundle\Security\ApiTokenAuthenticator;
use Augias\ApiBundle\Security\Provider\ApiTokenUserProvider;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

final class AugiasApiExtensionTest extends AbstractExtensionTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @return AugiasApiExtension[]
     */
    protected function getContainerExtensions(): array
    {
        return [
            new AugiasApiExtension(),
        ];
    }

    public function testLoad(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(ApiTokenAuthenticator::class);
        $this->assertContainerBuilderHasService(ApiTokenUserProvider::class);
        $this->assertContainerBuilderHasService(AuthenticationSuccessHandler::class);
        $this->assertContainerBuilderHasService(AuthenticationFailHandler::class);
        $this->assertContainerBuilderHasService(ApiTokenManager::class);
    }
}
