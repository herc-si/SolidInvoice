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

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Model\PendingDeclaration;
use Augias\AccountingBundle\Repository\AccountingPeriodRepository;
use Augias\AccountingBundle\Repository\DeclarationRepository;
use Augias\CoreBundle\Entity\Company;
use DateTimeImmutable;

/**
 * Periods that have ended and have not been declared.
 *
 * Read-only on purpose. {@see DeclarationBuilder} computes and persists a
 * declaration the first time someone opens a period's screen, and a dashboard
 * that quietly wrote rows every time it was rendered would be creating
 * accounting records as a side effect of looking at a page.
 *
 * One thing it cannot see: periods are created on demand, when an entry is
 * assigned to one. A quarter with no revenue at all therefore has no period row
 * and is not reported here, even though a regime may still require a nil
 * return for it. Creating those is the period manager's job, on a schedule,
 * not a widget's.
 *
 * @see \Augias\AccountingBundle\Tests\Dashboard\DeclarationsDueWidgetTest
 */
final readonly class PendingDeclarationFinder
{
    public function __construct(
        private AccountingPeriodRepository $periodRepository,
        private DeclarationRepository $declarationRepository,
    ) {
    }

    /**
     * @param int $limit how many to report — the caller has a card to fill, not a page
     * @return list<PendingDeclaration>
     */
    public function find(
        Company $company,
        PeriodType $type,
        int $limit,
        ?DateTimeImmutable $on = null,
    ): array {
        $on ??= new DateTimeImmutable('today');

        return array_map(
            fn (AccountingPeriod $period): PendingDeclaration => new PendingDeclaration(
                $period,
                $this->declarationRepository->findForPeriod($period),
            ),
            $this->periodRepository->findEndedAndUndeclared($company, $type, $on, $limit),
        );
    }
}
