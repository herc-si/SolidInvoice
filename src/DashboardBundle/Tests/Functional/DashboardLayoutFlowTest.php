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

namespace Augias\DashboardBundle\Tests\Functional;

use Augias\CoreBundle\Test\Traits\DoctrineTestTrait;
use Augias\DashboardBundle\Enum\WidgetWidth;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Layout\DashboardLayout;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Enum\UserSettingType;
use Augias\UserBundle\Repository\UserSettingRepository;
use PHPUnit\Framework\Attributes\Group;
use SensitiveParameter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * The dashboard end to end: default arrangement, a stored arrangement, and the
 * round trip through the save endpoint.
 *
 * The unit tests prove the resolver reconciles correctly; this proves the page
 * actually renders what it resolved, with the identifiers the JavaScript needs.
 */
#[Group('functional')]
final class DashboardLayoutFlowTest extends WebTestCase
{
    use DoctrineTestTrait;
    use EnsureApplicationInstalled;
    use HasBrowser;

    private UserSettingRepository $userSettingRepository;

    protected function setUp(): void
    {
        $repository = self::getContainer()->get(UserSettingRepository::class);
        self::assertInstanceOf(UserSettingRepository::class, $repository);
        $this->userSettingRepository = $repository;
    }

    public function testRendersTheDefaultArrangementForAUserWhoHasNeverCustomisedIt(): void
    {
        $user = $this->createUser();

        $this->browser()
            ->actingAs($user)
            ->visit('/dashboard')
            ->assertSuccessful()
            ->assertSeeIn('title', 'Dashboard')
            // The wrapper carries the registry id, which is the contract the
            // layout controller writes back against.
            ->assertSeeElement('[data-widget-id="outstanding_total"]')
            ->assertSeeElement('[data-widget-id="revenue_chart"]')
            ->assertSeeElement('[data-widget-id="quick_actions"]')
            // Each zone is a Sortable container.
            ->assertSeeElement('[data-zone="top"]')
            ->assertSeeElement('[data-zone="left_column"]')
            ->assertSeeElement('[data-zone="right_column"]')
            ->assertSeeElement('[data-controller="dashboard-layout"]')
            // The declared width reaches the markup, where the stylesheet turns
            // it into a span. The four stat tiles ship a quarter each, so the top
            // band fills exactly once across.
            ->assertSeeElement('[data-widget-id="outstanding_total"][data-widget-width="quarter"]')
            ->assertSeeElement('[data-widget-id="revenue_chart"][data-widget-width="full"]');
    }

    /**
     * A width the user chose beats the one the widget declares, and it survives
     * the trip through `user_settings` — which is the whole point of storing it
     * next to the zone rather than deriving it from the widget every time.
     */
    public function testAStoredWidthOverridesTheDeclaredDefault(): void
    {
        $user = $this->createUser();

        $this->userSettingRepository->saveSetting(
            $user,
            UserSettingType::DashboardLayout,
            json_encode((new DashboardLayout(
                [['id' => 'outstanding_total', 'zone' => WidgetZone::Top, 'width' => WidgetWidth::Half]],
            ))->toArray(), JSON_THROW_ON_ERROR),
        );

        $this->browser()
            ->actingAs($user)
            ->visit('/dashboard')
            ->assertSuccessful()
            ->assertSeeElement('[data-widget-id="outstanding_total"][data-widget-width="half"]')
            // Untouched neighbours keep the width their widget declares.
            ->assertSeeElement('[data-widget-id="total_revenue"][data-widget-width="quarter"]');
    }

    public function testAStoredArrangementIsHonouredAndHiddenWidgetsAreOffered(): void
    {
        $user = $this->createUser();

        $this->userSettingRepository->saveSetting(
            $user,
            UserSettingType::DashboardLayout,
            json_encode((new DashboardLayout(
                [['id' => 'quick_actions', 'zone' => WidgetZone::Top]],
                ['revenue_chart'],
            ))->toArray(), JSON_THROW_ON_ERROR),
        );

        $this->browser()
            ->actingAs($user)
            ->visit('/dashboard')
            ->assertSuccessful()
            // Moved out of the right column and into the top band.
            ->assertSeeElement('[data-zone="top"] [data-widget-id="quick_actions"]')
            // Hidden: not rendered anywhere, but offered back in the picker.
            ->assertNotSeeElement('.dashboard-widget[data-widget-id="revenue_chart"]')
            ->assertSeeElement('.dashboard-picker-item[data-widget-id="revenue_chart"]');
    }

    public function testTheSaveEndpointRefusesARequestWithoutAToken(): void
    {
        $user = $this->createUser();

        $this->browser()
            ->actingAs($user)
            ->post('/dashboard/layout', [
                'json' => ['widgets' => [['id' => 'outstanding_total', 'zone' => 'top']]],
            ])
            ->assertStatus(400);
    }

    private function createUser(string $email = 'dashboard@example.com', #[SensitiveParameter] string $password = 'password'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setEnabled(true);
        $user->setVerified(true);
        $user->addCompany($this->company);

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
