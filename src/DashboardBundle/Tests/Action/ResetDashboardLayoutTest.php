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

namespace Augias\DashboardBundle\Tests\Action;

use Augias\CoreBundle\Test\Traits\FakerTestTrait;
use Augias\DashboardBundle\Action\ResetDashboardLayout;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Layout\DashboardLayout;
use Augias\DashboardBundle\Layout\DashboardLayoutManager;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[CoversClass(ResetDashboardLayout::class)]
final class ResetDashboardLayoutTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use FakerTestTrait;

    private User $user;

    private UserSettingRepository $repository;

    private EntityManagerInterface $em;

    private DashboardLayoutManager $manager;

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

        $factory = self::getContainer()->get(WidgetFactory::class);
        self::assertInstanceOf(WidgetFactory::class, $factory);

        $this->manager = new DashboardLayoutManager($this->repository, $factory, $security, new NullLogger());
        $this->manager->save(new DashboardLayout([['id' => 'outstanding_total', 'zone' => WidgetZone::RightColumn]]));
    }

    protected function tearDown(): void
    {
        foreach ($this->repository->findAll() as $setting) {
            $this->em->remove($setting);
        }

        $this->em->flush();

        parent::tearDown();
    }

    public function testDiscardsTheLayoutAndRedirectsToTheDashboard(): void
    {
        $response = $this->reset(validToken: true);

        self::assertStringEndsWith('/dashboard', $response->getTargetUrl());
        self::assertNull($this->repository->getSetting($this->user, UserSettingType::DashboardLayout));
    }

    public function testKeepsTheLayoutWhenTheTokenIsInvalid(): void
    {
        $this->reset(validToken: false);

        self::assertNotNull($this->repository->getSetting($this->user, UserSettingType::DashboardLayout));
    }

    private function reset(bool $validToken): RedirectResponse
    {
        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')
            ->willReturn($validToken);

        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertInstanceOf(UrlGeneratorInterface::class, $urlGenerator);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->request->set('_token', 'token');

        $action = new ResetDashboardLayout(
            $this->manager,
            $csrfTokenManager,
            $urlGenerator,
            new RequestStack([$request]),
        );

        return $action($request);
    }
}
