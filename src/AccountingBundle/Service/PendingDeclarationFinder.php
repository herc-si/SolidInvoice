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
use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Model\PendingDeclaration;
use Augias\AccountingBundle\Repository\AccountingPeriodRepository;
use Augias\AccountingBundle\Repository\DeclarationRepository;
use Augias\CoreBundle\Entity\Company;
use DateTimeImmutable;
use function array_map;
use function array_slice;
use function usort;

/**
 * Periods that have ended and have not been declared.
 *
 * Read-only on purpose. {@see DeclarationBuilder} computes and persists a
 * declaration the first time someone opens a period's screen, and a dashboard
 * that quietly wrote rows every time it was rendered would be creating
 * accounting records as a side effect of looking at a page.
 *
 * Periods are created on demand, when an entry is assigned to one, so a quarter
 * with no revenue at all has no row. Those are reported too — as gaps, from
 * {@see PeriodCalendar} — because a regime may still want a nil return for one
 * and the user cannot file it against a period that does not exist. Creating it
 * stays their decision; nothing here writes.
 *
 * @see \Augias\AccountingBundle\Tests\Dashboard\DeclarationsDueWidgetTest
 */
final readonly class PendingDeclarationFinder
{
    public function __construct(
        private AccountingPeriodRepository $periodRepository,
        private DeclarationRepository $declarationRepository,
        private PeriodCalendar $calendar,
    ) {
    }

    /**
     * @param int $limit how many to report — the caller has a card to fill, not a page
     * @return list<PendingDeclaration>
     */
    public function find(
        Company $company,
        AccountingProfile $profile,
        int $limit,
        ?DateTimeImmutable $on = null,
    ): array {
        $on ??= new DateTimeImmutable('today');

        $pending = array_map(
            fn (AccountingPeriod $period): PendingDeclaration => PendingDeclaration::forPeriod(
                $period,
                $this->declarationRepository->findForPeriod($period),
            ),
            $this->periodRepository->findEndedAndUndeclared($company, $profile->declarationPeriodicity, $on, $limit),
        );

        // Only the gaps that have finished. A period still running has nothing
        // to declare yet, and offering to create it would be asking the user to
        // act on something that is not over.
        foreach ($this->calendar->missing($company, $profile, $on) as $gap) {
            if ($gap->hasEnded($on)) {
                $pending[] = PendingDeclaration::forMissing($gap);
            }
        }

        // Oldest first across both kinds: what has waited longest carries the
        // most consequence, whether or not it happens to have a row.
        usort(
            $pending,
            static fn (PendingDeclaration $a, PendingDeclaration $b): int
                => $b->daysSincePeriodEnded($on) <=> $a->daysSincePeriodEnded($on),
        );

        return array_slice($pending, 0, $limit);
    }
}
