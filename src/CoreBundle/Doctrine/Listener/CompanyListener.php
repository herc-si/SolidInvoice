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
use Augias\CoreBundle\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Uid\Ulid;

/**
 * @see \Augias\CoreBundle\Tests\Doctrine\Listener\CompanyListenerTest
 */
#[AsDoctrineListener(Events::prePersist)]
final readonly class CompanyListener
{
    public function __construct(
        private CompanySelector $companySelector,
    ) {
    }

    /**
     * Maps additional metadata.
     */
    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();

        $em = $eventArgs->getObjectManager();
        $metaData = $em->getClassMetadata($object::class);

        if ($metaData->hasAssociation('company')) {
            if ($metaData->getPropertyAccessor('company')->getUnderlyingReflector()->isInitialized($object)) {
                return;
            }

            $repository = $em->getRepository(Company::class);
            $companyId = $this->companySelector->getCompany();

            if ($companyId instanceof Ulid) {
                $object->setCompany($repository->find($companyId));
            }
        }
    }
}
