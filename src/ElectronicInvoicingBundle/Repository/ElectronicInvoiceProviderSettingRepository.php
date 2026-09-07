<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\ElectronicInvoicingBundle\Repository;

use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<ElectronicInvoiceProviderSetting>
 */
final class ElectronicInvoiceProviderSettingRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElectronicInvoiceProviderSetting::class);
    }

    public function findActive(): ?ElectronicInvoiceProviderSetting
    {
        return $this->findOneBy(['active' => true]);
    }

    /**
     * Meant to be called with the company filter disabled, where `findActive()`
     * (which relies on that filter to scope to "the current company") cannot
     * be used, since the company must be given explicitly instead.
     */
    public function findActiveForCompany(Ulid $companyId, string $provider): ?ElectronicInvoiceProviderSetting
    {
        return $this->findOneBy(['company' => $companyId, 'provider' => $provider, 'active' => true]);
    }

    public function delete(ElectronicInvoiceProviderSetting $setting): void
    {
        $this->getEntityManager()->remove($setting);
        $this->getEntityManager()->flush();
    }
}
