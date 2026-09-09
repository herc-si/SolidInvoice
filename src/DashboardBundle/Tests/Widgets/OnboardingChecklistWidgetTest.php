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

namespace Augias\DashboardBundle\Tests\Widgets;

use Augias\CoreBundle\Test\Factory\CompanyFactory;
use Augias\DashboardBundle\Checklist\ChecklistManager;
use Augias\DashboardBundle\Widgets\OnboardingChecklistWidget;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\UserBundle\Test\Factory\UserFactory;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class OnboardingChecklistWidgetTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * Applicability moved out of getData() and into supports(), so a dismissed or
     * anonymous checklist is now settled before any of the items run their
     * completion queries.
     */
    public function testDoesNotApplyWhenNoUserIsLoggedIn(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')
            ->willReturn(null);

        $manager = self::getContainer()->get(ChecklistManager::class);
        $widget = new OnboardingChecklistWidget($manager, $security, new NullLogger());

        self::assertFalse($widget->supports());
    }

    public function testDoesNotApplyOnceTheChecklistIsDismissed(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]]);

        $manager = self::getContainer()->get(ChecklistManager::class);
        $manager->dismiss($user);

        $security = $this->createStub(Security::class);
        $security->method('getUser')
            ->willReturn($user);

        $widget = new OnboardingChecklistWidget($manager, $security, new NullLogger());

        self::assertFalse($widget->supports());
    }

    public function testAppliesForAUserWhoHasNotDismissedIt(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]]);

        $security = $this->createStub(Security::class);
        $security->method('getUser')
            ->willReturn($user);

        $manager = self::getContainer()->get(ChecklistManager::class);
        $widget = new OnboardingChecklistWidget($manager, $security, new NullLogger());

        self::assertTrue($widget->supports());
    }

    public function testGetDataReturnsProgressWhenChecklistShouldBeShown(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]]);

        $security = $this->createStub(Security::class);
        $security->method('getUser')
            ->willReturn($user);

        $manager = self::getContainer()->get(ChecklistManager::class);
        $widget = new OnboardingChecklistWidget($manager, $security, new NullLogger());

        $data = $widget->getData();

        self::assertArrayHasKey('show', $data);
        self::assertTrue($data['show']);
        self::assertArrayHasKey('progress', $data);
        self::assertIsObject($data['progress']);
    }

    public function testGetTemplateReturnsCorrectTemplatePath(): void
    {
        $security = $this->createStub(Security::class);
        $manager = self::getContainer()->get(ChecklistManager::class);

        $widget = new OnboardingChecklistWidget($manager, $security, new NullLogger());

        self::assertSame('@AugiasDashboard/Widget/onboarding_checklist.html.twig', $widget->getTemplate());
    }
}
