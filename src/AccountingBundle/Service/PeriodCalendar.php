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

use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Model\MissingPeriod;
use Augias\AccountingBundle\Repository\AccountingPeriodRepository;
use Augias\AccountingBundle\Repository\LedgerEntryRepository;
use Augias\CoreBundle\Entity\Company;
use DateTimeImmutable;

/**
 * The periods a company ought to have, and which of them do not exist.
 *
 * A period is created when an entry is filed into it, so a quarter in which
 * nothing was received never comes into being. Under a regime that still wants
 * a nil return for it, that silence is the problem: the declarations page lists
 * rows, so a quarter with no row is a quarter nobody is reminded about, and a
 * missed filing is a penalty that grows.
 *
 * Nothing here writes. It works out what is missing so a screen can show it;
 * creating the row is a decision the user takes, and
 * {@see \Augias\AccountingBundle\Action\CreatePeriod} takes it through the same
 * factory the lazy path uses.
 *
 * @see \Augias\AccountingBundle\Tests\Service\PeriodCalendarTest
 */
final readonly class PeriodCalendar
{
    /**
     * A guard, not a business rule. Any company whose start date or oldest entry
     * is further back than this has something wrong with its data, and walking
     * a century of quarters to find that out helps nobody.
     */
    private const int MAX_PERIODS = 60;

    public function __construct(
        private AccountingPeriodRepository $periodRepository,
        private LedgerEntryRepository $entryRepository,
    ) {
    }

    /**
     * Periods between the company's first day of trading and today that have no
     * row, oldest first.
     *
     * @return list<MissingPeriod>
     */
    public function missing(
        Company $company,
        AccountingProfile $profile,
        ?DateTimeImmutable $on = null,
        ?PeriodType $type = null,
    ): array {
        $on = ($on ?? new DateTimeImmutable('today'))->setTime(0, 0);
        // The books' own rhythm unless asked otherwise: VAT can run on a cycle
        // of its own, and its periods are missing or not independently of the
        // ones the books are sealed on.
        $type ??= $profile->declarationPeriodicity;
        $from = $this->firstDayOfTrading($company, $profile, $on);

        $existing = [];

        foreach ($this->periodRepository->findBetween($company, $type, $from, $on) as $period) {
            $existing[$period->getYear() . ':' . $period->getOrdinal()] = true;
        }

        $missing = [];
        $cursor = $type->startOf($from);
        $guard = 0;

        // Walked forward one period at a time rather than derived arithmetically:
        // the step differs per type and months are not all the same length, and
        // startOf()/endOf() already know all of that.
        while ($cursor <= $on && $guard++ < self::MAX_PERIODS) {
            $candidate = MissingPeriod::covering($type, $cursor);

            if (! isset($existing[$candidate->year . ':' . $candidate->ordinal])) {
                $missing[] = $candidate;
            }

            $cursor = $candidate->endDate->modify('+1 day');
        }

        return $missing;
    }

    /**
     * The earliest date the company is known to have been trading on.
     *
     * The declared start of activity first, because that is literally what it
     * records. Failing that the oldest entry in the books, which is the earliest
     * date there is evidence for. Failing both, today: a company with no start
     * date and no entries has nothing to show it ever traded, and inventing nil
     * quarters for it would be inventing an obligation.
     */
    private function firstDayOfTrading(
        Company $company,
        AccountingProfile $profile,
        DateTimeImmutable $on,
    ): DateTimeImmutable {
        if ($profile->activityStartDate instanceof DateTimeImmutable) {
            return $profile->activityStartDate->setTime(0, 0);
        }

        $earliest = $this->entryRepository->earliestEntryDate($company);

        return $earliest instanceof DateTimeImmutable ? $earliest->setTime(0, 0) : $on;
    }
}
