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

namespace Augias\AccountingBundle\Action\Declaration;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\Declaration;
use Augias\AccountingBundle\Model\MissingPeriod;
use Augias\AccountingBundle\Regime\RegimeInterface;
use Augias\AccountingBundle\Regime\RegimeRegistry;
use Augias\AccountingBundle\Repository\AccountingPeriodRepository;
use Augias\AccountingBundle\Repository\DeclarationRepository;
use Augias\AccountingBundle\Service\AccountingProfileProvider;
use Augias\AccountingBundle\Service\CurrentCompany;
use Augias\AccountingBundle\Service\PeriodCalendar;
use Augias\CoreBundle\Entity\Company;
use DateTimeImmutable;
use Symfony\Bridge\Twig\Attribute\Template;
use function array_reverse;
use function usort;

/**
 * Every period of the year and where its declaration stands.
 *
 * Periods rather than declarations, because the list has to show what has NOT
 * been declared as well as what has: a quarter that was closed and then
 * forgotten is exactly the thing this page exists to make visible, and it has
 * no declaration row to be listed by.
 *
 * By the same argument the list also shows periods with no row at all. A
 * quarter in which nothing was received never comes into being — periods are
 * created when an entry is filed into one — and under a regime that still wants
 * a nil return for it, listing only what exists would hide precisely the
 * quarter most likely to be forgotten. Those rows carry no id and nothing has
 * been written; creating one is a button, and a decision.
 */
final readonly class Index
{
    public function __construct(
        private AccountingProfileProvider $profileProvider,
        private RegimeRegistry $registry,
        private CurrentCompany $currentCompany,
        private AccountingPeriodRepository $periodRepository,
        private DeclarationRepository $declarationRepository,
        private PeriodCalendar $calendar,
    ) {
    }

    /**
     * @return array{
     *     regime: RegimeInterface|null,
     *     year: int,
     *     rows: list<array{period: AccountingPeriod, declaration: Declaration|null}>,
     *     missing: list<MissingPeriod>
     * }
     */
    #[Template('@AugiasAccounting/Declaration/index.html.twig')]
    public function __invoke(): array
    {
        $profile = $this->profileProvider->forCompany();
        $regime = $this->registry->forProfile($profile);
        $company = $this->currentCompany->get();
        $year = (int) new DateTimeImmutable('today')->format('Y');

        if (! $regime instanceof RegimeInterface || ! $company instanceof Company) {
            return ['regime' => null, 'year' => $year, 'rows' => [], 'missing' => []];
        }

        $periods = $this->periodRepository->findForYear($company, $profile->declarationPeriodicity, $year);

        // Most recent first: the one a user comes here to deal with is the one
        // that just ended.
        usort($periods, static fn (AccountingPeriod $a, AccountingPeriod $b): int => $b->getOrdinal() <=> $a->getOrdinal());

        $rows = [];

        foreach ($periods as $period) {
            $rows[] = [
                'period' => $period,
                'declaration' => $this->declarationRepository->findForPeriod($period),
            ];
        }

        // Newest first, to match the rows above them.
        $missing = array_reverse($this->calendar->missing($company, $profile));

        return [
            'regime' => $regime,
            'year' => $year,
            'rows' => $rows,
            'missing' => $missing,
        ];
    }
}
