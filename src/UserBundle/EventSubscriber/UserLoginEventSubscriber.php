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

namespace Augias\UserBundle\EventSubscriber;

use Augias\CoreBundle\Company\ResolvedHost;
use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Listener\HostRoutingListener;
use Augias\UserBundle\Entity\User;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @see \Augias\UserBundle\Tests\EventSubscriber\UserLoginEventSubscriberTest
 */
final readonly class UserLoginEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLogin',
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        assert($user instanceof User);

        $request = $this->requestStack->getMainRequest();
        $resolved = $request?->attributes->get(HostRoutingListener::REQUEST_ATTR);

        if ($resolved instanceof ResolvedHost && $resolved->isCustomDomain()) {
            if (! $resolved->company instanceof Company) {
                throw new BadCredentialsException();
            }

            $companyId = $resolved->company->getId();
            $belongs = $user->getCompanies()->exists(
                static fn ($key, $company) => $company->getId()->equals($companyId)
            );

            if (! $belongs) {
                throw new BadCredentialsException();
            }
        }
    }

    public function onLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        assert($user instanceof User);

        // The last_login column is mapped as a mutable datetime, and DBAL 4 rejects
        // immutable values for it, so use a mutable Carbon instance here.
        $user->setLastLogin(Carbon::now());

        $this->entityManager->getRepository(User::class)->save($user);
    }
}
