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

namespace Augias\TaxBundle\Service;

use Augias\SettingsBundle\SystemConfig;
use Augias\TaxBundle\Entity\Tax;
use Augias\TaxBundle\Repository\TaxRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Whether tax should be offered on billing documents at all.
 *
 * Two separate reasons say no, and both used to be answered ad hoc. A company
 * that has configured no rates has nothing to pick from — that part was already
 * checked, six times, by calling the repository directly. A company outside the
 * scope of VAT has no business being shown the field even if rates exist,
 * because a micro-entrepreneur in franchise en base charges none.
 *
 * The two questions are joined here so the form types, the live components and
 * the menu cannot drift apart on the answer, and so the reason for the answer
 * has somewhere to be written down.
 *
 * This does not decide what the totals are — {@see \Augias\TaxBundle\Calculator\TaxCalculator}
 * makes that call on its own, from the same setting. This only decides what the
 * user is shown.
 *
 * @see \Augias\TaxBundle\Tests\Service\TaxAvailabilityTest
 */
final readonly class TaxAvailability
{
    public function __construct(
        private ManagerRegistry $registry,
        private SystemConfig $systemConfig,
    ) {
    }

    public function isOffered(): bool
    {
        if ($this->systemConfig->isVatExempt()) {
            return false;
        }

        $repository = $this->registry->getManager()->getRepository(Tax::class);

        return $repository instanceof TaxRepository && $repository->taxRatesConfigured();
    }
}
