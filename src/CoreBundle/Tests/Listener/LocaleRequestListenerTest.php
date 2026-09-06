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

namespace Augias\CoreBundle\Tests\Listener;

use Augias\CoreBundle\Listener\LocaleRequestListener;
use Augias\SettingsBundle\SystemConfig;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Entity\UserSetting;
use Augias\UserBundle\Enum\UserSettingType;
use Augias\UserBundle\Repository\UserSettingRepositoryInterface;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(LocaleRequestListener::class)]
final class LocaleRequestListenerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testDoesNothingBeforeInstall(): void
    {
        $security = M::mock(Security::class);
        $security->shouldNotReceive('getUser');
        $userSettings = M::mock(UserSettingRepositoryInterface::class);
        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldNotReceive('get');

        $listener = new LocaleRequestListener($security, $userSettings, $systemConfig, null);

        $request = $this->createRequest();
        $listener->onKernelRequest($this->createEvent($request));

        self::assertSame('en', $request->getLocale());
        self::assertFalse($request->getSession()->has('_locale'));
    }

    public function testUsesTheAuthenticatedUsersLocalePreference(): void
    {
        $user = new User();
        $security = M::mock(Security::class);
        $security->shouldReceive('getUser')->once()->andReturn($user);

        $setting = new UserSetting();
        $setting->setValue('fr');

        $userSettings = M::mock(UserSettingRepositoryInterface::class);
        $userSettings
            ->shouldReceive('getSetting')
            ->once()
            ->with($user, UserSettingType::Locale)
            ->andReturn($setting);

        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldNotReceive('get');

        $listener = new LocaleRequestListener($security, $userSettings, $systemConfig, '2024');

        $request = $this->createRequest();
        $listener->onKernelRequest($this->createEvent($request));

        self::assertSame('fr', $request->getLocale());
        self::assertSame('fr', $request->getSession()->get('_locale'));
    }

    public function testFallsBackToTheSystemDefaultWhenTheUserHasNoPreference(): void
    {
        $user = new User();
        $security = M::mock(Security::class);
        $security->shouldReceive('getUser')->once()->andReturn($user);

        $userSettings = M::mock(UserSettingRepositoryInterface::class);
        $userSettings
            ->shouldReceive('getSetting')
            ->once()
            ->with($user, UserSettingType::Locale)
            ->andReturn(null);

        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig
            ->shouldReceive('get')
            ->once()
            ->with(SystemConfig::LOCALE_CONFIG_PATH)
            ->andReturn('fr');

        $listener = new LocaleRequestListener($security, $userSettings, $systemConfig, '2024');

        $request = $this->createRequest();
        $listener->onKernelRequest($this->createEvent($request));

        self::assertSame('fr', $request->getLocale());
        self::assertSame('fr', $request->getSession()->get('_locale'));
    }

    public function testLeavesTheRequestUntouchedWhenNoLocaleIsResolved(): void
    {
        $security = M::mock(Security::class);
        $security->shouldReceive('getUser')->once()->andReturn(null);

        $userSettings = M::mock(UserSettingRepositoryInterface::class);
        $userSettings->shouldNotReceive('getSetting');

        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig
            ->shouldReceive('get')
            ->once()
            ->with(SystemConfig::LOCALE_CONFIG_PATH)
            ->andReturn(null);

        $listener = new LocaleRequestListener($security, $userSettings, $systemConfig, '2024');

        $request = $this->createRequest();
        $listener->onKernelRequest($this->createEvent($request));

        self::assertSame('en', $request->getLocale());
        self::assertFalse($request->getSession()->has('_locale'));
    }

    public function testSkipsStatelessRequests(): void
    {
        $security = M::mock(Security::class);
        $security->shouldNotReceive('getUser');
        $userSettings = M::mock(UserSettingRepositoryInterface::class);
        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldNotReceive('get');

        $listener = new LocaleRequestListener($security, $userSettings, $systemConfig, '2024');

        $request = $this->createRequest();
        $request->attributes->set('_stateless', true);
        $listener->onKernelRequest($this->createEvent($request));

        self::assertSame('en', $request->getLocale());
    }

    private function createRequest(): Request
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function createEvent(Request $request): RequestEvent
    {
        return new RequestEvent(M::mock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
