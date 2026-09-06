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

namespace Augias\CoreBundle\Doctrine\Listener;

use Augias\CoreBundle\Company\CompanySelector;
use Augias\CoreBundle\Company\DefaultData;
use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Event\CompanyCreatedEvent;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use JsonException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsEntityListener(Events::postPersist, entity: Company::class)]
final readonly class CompanyCreatedListener
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private DefaultData $defaultData,
        private CompanySelector $companySelector,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function postPersist(Company $company): void
    {
        $this->eventDispatcher->dispatch(new CompanyCreatedEvent($company));

        $this->companySelector->switchCompany($company->getId());

        /** @TODO: Need a different way to specify the currency and not add it to the company entity */
        ($this->defaultData)($company, [
            'currency' => $company->currency,
            // Carries the install-time (or the creating user's own) locale choice
            // onto the new company's default locale setting, instead of always
            // seeding it as English - see SystemConfigProvider::provide().
            'locale' => $this->requestStack->getCurrentRequest()?->getLocale(),
        ]);
    }
}
