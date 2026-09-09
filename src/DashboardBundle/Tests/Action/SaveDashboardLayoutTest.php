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
use Augias\DashboardBundle\Action\SaveDashboardLayout;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[CoversClass(SaveDashboardLayout::class)]
final class SaveDashboardLayoutTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use FakerTestTrait;

    private User $user;

    private UserSettingRepository $repository;

    private EntityManagerInterface $em;

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
    }

    protected function tearDown(): void
    {
        foreach ($this->repository->findAll() as $setting) {
            $this->em->remove($setting);
        }

        $this->em->flush();

        parent::tearDown();
    }

    public function testStoresTheArrangement(): void
    {
        $response = $this->post($this->action(), [
            'widgets' => [
                ['id' => 'hero_stats', 'zone' => 'top'],
                ['id' => 'revenue_chart', 'zone' => 'right_column'],
            ],
            'hidden' => ['recent_activity'],
        ]);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([
            'v' => 1,
            'widgets' => [
                ['id' => 'hero_stats', 'zone' => 'top'],
                ['id' => 'revenue_chart', 'zone' => 'right_column'],
            ],
            'hidden' => ['recent_activity'],
        ], $this->storedLayout());
    }

    /**
     * The layout is a state-changing POST driven by fetch(), so a missing or
     * wrong token is refused outright rather than merely ignored.
     */
    public function testRejectsAnInvalidCsrfToken(): void
    {
        $response = $this->post($this->action(validToken: false), ['widgets' => [['id' => 'hero_stats', 'zone' => 'top']]]);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertNull($this->storedLayout());
    }

    public function testRejectsAPayloadThatIsNotJson(): void
    {
        $request = new Request(server: ['CONTENT_TYPE' => 'application/json'], content: 'not json at all');
        $request->headers->set('X-CSRF-Token', 'token');

        self::assertSame(Response::HTTP_BAD_REQUEST, ($this->action())($request)->getStatusCode());
    }

    public function testRejectsAJsonScalar(): void
    {
        $request = new Request(server: ['CONTENT_TYPE' => 'application/json'], content: '"just a string"');
        $request->headers->set('X-CSRF-Token', 'token');

        self::assertSame(Response::HTTP_BAD_REQUEST, ($this->action())($request)->getStatusCode());
    }

    /**
     * Unknown ids are dropped rather than rejected: the request is well formed,
     * the application has simply moved on since the page was loaded, and failing
     * it would leave the user dragging cards that never stay put.
     */
    public function testDropsWidgetsTheApplicationDoesNotHave(): void
    {
        $this->post($this->action(), [
            'widgets' => [
                ['id' => 'hero_stats', 'zone' => 'top'],
                ['id' => 'widget_from_a_future_release', 'zone' => 'top'],
            ],
            'hidden' => ['also_not_a_widget'],
        ]);

        $stored = $this->storedLayout();

        self::assertNotNull($stored);
        self::assertSame(['hero_stats'], array_column($stored['widgets'], 'id'));
        self::assertSame([], $stored['hidden']);
    }

    /**
     * hero_stats ships as non-removable. A payload hiding it is not honoured, and
     * the widget is put back rather than quietly dropped from both lists.
     */
    public function testRefusesToHideAPinnedWidget(): void
    {
        $this->post($this->action(), [
            'widgets' => [['id' => 'revenue_chart', 'zone' => 'left_column']],
            'hidden' => ['hero_stats'],
        ]);

        $stored = $this->storedLayout();

        self::assertNotNull($stored);
        self::assertSame(['revenue_chart', 'hero_stats'], array_column($stored['widgets'], 'id'));
        self::assertSame([], $stored['hidden']);
    }

    private function action(bool $validToken = true): SaveDashboardLayout
    {
        $csrfTokenManager = $this->createStub(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')
            ->willReturn($validToken);

        // The real Security reads a token this test never creates, so the current
        // user is stubbed rather than logged in: what is under test is the
        // action's handling of the payload, not authentication.
        $security = $this->createStub(Security::class);
        $security->method('getUser')
            ->willReturn($this->user);

        $factory = self::getContainer()->get(WidgetFactory::class);
        self::assertInstanceOf(WidgetFactory::class, $factory);

        return new SaveDashboardLayout(
            new DashboardLayoutManager($this->repository, $factory, $security, new NullLogger()),
            $csrfTokenManager,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(SaveDashboardLayout $action, array $payload): Response
    {
        $request = new Request(
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
        $request->headers->set('X-CSRF-Token', 'token');

        return $action($request);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function storedLayout(): ?array
    {
        $value = $this->repository->getSetting($this->user, UserSettingType::DashboardLayout)?->getValue();

        return null === $value ? null : json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }
}
