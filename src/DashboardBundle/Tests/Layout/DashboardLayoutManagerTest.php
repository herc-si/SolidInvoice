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

namespace Augias\DashboardBundle\Tests\Layout;

use Augias\CoreBundle\Test\Traits\FakerTestTrait;
use Augias\DashboardBundle\Enum\WidgetWidth;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Layout\DashboardLayout;
use Augias\DashboardBundle\Layout\DashboardLayoutManager;
use Augias\DashboardBundle\Tests\Fixtures\StubWidget;
use Augias\DashboardBundle\WidgetFactory;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Enum\UserSettingType;
use Augias\UserBundle\Repository\UserSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;

#[CoversClass(DashboardLayoutManager::class)]
final class DashboardLayoutManagerTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use FakerTestTrait;

    private User $user;

    private UserSettingRepository $repository;

    private EntityManagerInterface $em;

    private DashboardLayoutManager $manager;

    private Security $security;

    protected function setUp(): void
    {
        parent::setUp();

        $em = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $this->em = $em;

        $repository = self::getContainer()->get(UserSettingRepository::class);
        self::assertInstanceOf(UserSettingRepository::class, $repository);
        $this->repository = $repository;

        $this->user = new User();
        $this->user->setEmail($this->getFaker()->email())
            ->setPassword($this->getFaker()->password())
            ->addCompany($this->company);

        $this->em->persist($this->user);
        $this->em->flush();

        $security = $this->createStub(Security::class);
        $security->method('getUser')
            ->willReturn($this->user);

        $this->security = $security;

        $factory = self::getContainer()->get(WidgetFactory::class);
        self::assertInstanceOf(WidgetFactory::class, $factory);

        $this->manager = $this->managerWith($factory);
    }

    protected function tearDown(): void
    {
        foreach ($this->repository->findAll() as $setting) {
            $this->em->remove($setting);
        }

        $this->em->flush();

        parent::tearDown();
    }

    public function testLoadReturnsNullBeforeAnythingIsSaved(): void
    {
        self::assertNull($this->manager->load());
    }

    public function testSavedLayoutComesBack(): void
    {
        $this->manager->save(new DashboardLayout(
            [['id' => 'outstanding_total', 'zone' => WidgetZone::RightColumn]],
            ['revenue_chart'],
        ));

        $loaded = $this->manager->load();

        self::assertNotNull($loaded);
        self::assertSame('outstanding_total', $loaded->visible[0]['id']);
        self::assertSame(WidgetZone::RightColumn, $loaded->visible[0]['zone']);
        self::assertSame(['revenue_chart'], $loaded->hidden);
    }

    public function testAStoredWidthSurvivesTheRoundTrip(): void
    {
        $this->manager->save(new DashboardLayout(
            [['id' => 'outstanding_total', 'zone' => WidgetZone::Top, 'width' => WidgetWidth::Half]],
        ));

        $loaded = $this->manager->load();

        self::assertNotNull($loaded);
        self::assertSame(WidgetWidth::Half, $loaded->visible[0]['width'] ?? null);
    }

    /**
     * A pinned widget is put back rather than hidden, whatever the payload says.
     *
     * Nothing the application ships is pinned today — the four stat tiles that
     * replaced the single "Key Figures" widget are each removable on purpose — so
     * the rule is exercised against a registry built here. Pointing it at the real
     * one would make this test a hostage to a placement decision it is not about.
     */
    public function testAPinnedWidgetIsPutBackRatherThanHidden(): void
    {
        $factory = new WidgetFactory();
        $factory->add(new StubWidget(), 'pinned', 'l', 'i', 'top', removable: false);
        $factory->add(new StubWidget(), 'ordinary', 'l', 'i', 'left_column');

        $manager = $this->managerWith($factory);
        $manager->save(new DashboardLayout(
            [['id' => 'ordinary', 'zone' => WidgetZone::LeftColumn]],
            ['pinned'],
        ));

        $loaded = $manager->load();

        self::assertNotNull($loaded);
        self::assertSame(['ordinary', 'pinned'], array_column($loaded->visible, 'id'));
        self::assertSame([], $loaded->hidden);
    }

    public function testSavingTwiceOverwritesRatherThanAccumulates(): void
    {
        $this->manager->save(new DashboardLayout([['id' => 'outstanding_total', 'zone' => WidgetZone::Top]]));
        $this->manager->save(new DashboardLayout([['id' => 'revenue_chart', 'zone' => WidgetZone::Top]]));

        $loaded = $this->manager->load();

        self::assertNotNull($loaded);
        // load() returns what was stored, not what will be rendered: outstanding_total
        // is absent from the second payload entirely, so it is neither visible
        // nor hidden here and LayoutResolver will treat it as new.
        self::assertSame(['revenue_chart'], array_column($loaded->visible, 'id'));
        self::assertCount(1, $this->repository->findAll());
    }

    /**
     * A layout that cannot be read must not take the dashboard with it: null
     * means "use the defaults", and the next save replaces whatever is corrupt.
     */
    public function testUnreadableStoredValueFallsBackToTheDefaults(): void
    {
        $this->repository->saveSetting($this->user, UserSettingType::DashboardLayout, '{not json');

        self::assertNull($this->manager->load());
    }

    public function testAStoredJsonScalarIsIgnored(): void
    {
        $this->repository->saveSetting($this->user, UserSettingType::DashboardLayout, '"a string"');

        self::assertNull($this->manager->load());
    }

    public function testResetRemovesTheSetting(): void
    {
        $this->manager->save(new DashboardLayout([['id' => 'outstanding_total', 'zone' => WidgetZone::Top]]));
        $this->manager->reset();

        self::assertNull($this->manager->load());
        self::assertNull($this->repository->getSetting($this->user, UserSettingType::DashboardLayout));
    }

    private function managerWith(WidgetFactory $factory): DashboardLayoutManager
    {
        return new DashboardLayoutManager($this->repository, $factory, $this->security, new NullLogger());
    }
}
