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

namespace Augias\CoreBundle\Tests\Twig\Extension;

use Augias\CoreBundle\AugiasCoreBundle;
use Augias\CoreBundle\Company\CompanySelector;
use Augias\CoreBundle\Pdf\Generator;
use Augias\CoreBundle\Twig\Extension\GlobalExtension;
use Augias\MoneyBundle\Calculator;
use Augias\SettingsBundle\SystemConfig;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class GlobalExtensionTest extends TestCase
{
    public function testAppVersionIsExposedWhenNotInSaasMode(): void
    {
        $globals = $this->createExtension(saasEnabled: false)->getGlobals();

        self::assertSame(AugiasCoreBundle::VERSION, $globals['app_version']);
    }

    public function testAppVersionIsHiddenInSaasMode(): void
    {
        $globals = $this->createExtension(saasEnabled: true)->getGlobals();

        self::assertNull($globals['app_version']);
    }

    private function createExtension(bool $saasEnabled): GlobalExtension
    {
        $toggler = $this->createMock(ToggleInterface::class);
        $toggler
            ->expects(self::once())
            ->method('isActive')
            ->with('saas_enabled')
            ->willReturn($saasEnabled);

        return new GlobalExtension(
            new Calculator(),
            $this->createStub(Generator::class),
            $this->createStub(SystemConfig::class),
            new RequestStack(),
            new CompanySelector($this->createStub(ManagerRegistry::class)),
            'default',
            $toggler,
        );
    }
}
