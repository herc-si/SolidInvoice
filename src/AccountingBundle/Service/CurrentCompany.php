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

namespace Augias\AccountingBundle\Service;

use Augias\CoreBundle\Company\CompanySelector;
use Augias\CoreBundle\Entity\Company;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * The active tenant as an entity.
 *
 * Every accounting query takes its company explicitly — the repositories are
 * written that way so the cron jobs can run with the multi-tenancy filter
 * switched off — but the screens only have the selector, which holds an id.
 * This resolves the one into the other in a single place instead of at the top
 * of every action.
 */
final readonly class CurrentCompany
{
    public function __construct(
        private CompanySelector $companySelector,
        private ManagerRegistry $doctrine,
    ) {
    }

    public function get(): ?Company
    {
        $companyId = $this->companySelector->getCompany();

        if (null === $companyId) {
            return null;
        }

        $company = $this->doctrine->getRepository(Company::class)->find($companyId);

        return $company instanceof Company ? $company : null;
    }

    /**
     * @throws RuntimeException when there is no active company — a state the
     *                          screens cannot render anything meaningful in
     */
    public function require(): Company
    {
        $company = $this->get();

        if (! $company instanceof Company) {
            throw new RuntimeException('No company is currently selected.');
        }

        return $company;
    }
}
