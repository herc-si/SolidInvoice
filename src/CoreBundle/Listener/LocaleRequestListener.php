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

namespace Augias\CoreBundle\Listener;

use Augias\SettingsBundle\SystemConfig;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Enum\UserSettingType;
use Augias\UserBundle\Repository\UserSettingRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Resolves the request locale in priority order: the signed-in user's own
 * preference (profile setting), then the company's default locale (system
 * setting), falling back to whatever the framework's own LocaleListener
 * already resolved (session/%kernel.default_locale%) if neither is set.
 *
 * Runs after CompanyEventSubscriber (priority 7) so company context is
 * already resolved, and after LocaleAwareListener (priority 15) has synced
 * the Translator for this request - so a locale change (via the profile
 * form or the settings page) only takes full effect from the *next* request
 * onward. EditProfile compensates for its own save by seeding the session
 * value directly, so a user changing their own preference sees it applied
 * immediately.
 *
 * @see \Augias\CoreBundle\Tests\Listener\LocaleRequestListenerTest
 */
final readonly class LocaleRequestListener implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private UserSettingRepositoryInterface $userSettingRepository,
        private SystemConfig $systemConfig,
        private ?string $installed = null,
    ) {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 6],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest() || null === $this->installed || '' === $this->installed) {
            return;
        }

        $request = $event->getRequest();

        if ($request->attributes->get('_stateless')) {
            return;
        }

        $locale = $this->resolveUserLocale() ?? $this->resolveSystemLocale();

        if (null === $locale || '' === $locale) {
            return;
        }

        $request->setLocale($locale);
        $request->getSession()->set('_locale', $locale);
    }

    private function resolveUserLocale(): ?string
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return null;
        }

        return $this->userSettingRepository->getSetting($user, UserSettingType::Locale)?->getValue();
    }

    private function resolveSystemLocale(): ?string
    {
        try {
            return $this->systemConfig->get(SystemConfig::LOCALE_CONFIG_PATH);
        } catch (Throwable) {
            return null;
        }
    }
}
