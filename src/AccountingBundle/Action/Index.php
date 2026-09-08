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

namespace Augias\AccountingBundle\Action;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\ThresholdAlert;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Model\Threshold;
use Augias\AccountingBundle\Model\TurnoverSummary;
use Augias\AccountingBundle\Regime\RegimeInterface;
use Augias\AccountingBundle\Regime\RegimeRegistry;
use Augias\AccountingBundle\Repository\AccountingPeriodRepository;
use Augias\AccountingBundle\Repository\ThresholdAlertRepository;
use Augias\AccountingBundle\Service\AccountingProfileProvider;
use Augias\AccountingBundle\Service\CurrentCompany;
use Augias\AccountingBundle\Service\TurnoverCalculator;
use Augias\CoreBundle\Entity\Company;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use Symfony\Bridge\Twig\Attribute\Template;

/**
 * The accounting home page: where the company stands this year, and what it
 * still has to do about it.
 *
 * Until a regime is chosen there is nothing meaningful to show — an empty book
 * measured against limits the user never picked would be worse than no page at
 * all — so this hands the template the profile and lets it render either the
 * setup prompt or the books.
 *
 * The turnover shown is year-to-date rather than the current period's, because
 * that is what every limit on the page is expressed against.
 */
final readonly class Index
{
    public function __construct(
        private AccountingProfileProvider $profileProvider,
        private RegimeRegistry $registry,
        private CurrentCompany $currentCompany,
        private TurnoverCalculator $turnoverCalculator,
        private AccountingPeriodRepository $periodRepository,
        private ThresholdAlertRepository $alertRepository,
    ) {
    }

    /**
     * @return array{
     *     profile: AccountingProfile,
     *     regime: RegimeInterface|null,
     *     books: list<LedgerBook>,
     *     turnover: TurnoverSummary|null,
     *     limits: list<array{threshold: Threshold, used: int}>,
     *     period: AccountingPeriod|null,
     *     alerts: list<ThresholdAlert>,
     *     year: int
     * }
     */
    #[Template('@AugiasAccounting/Default/index.html.twig')]
    public function __invoke(): array
    {
        $profile = $this->profileProvider->forCompany();
        $regime = $this->registry->forProfile($profile);
        $company = $this->currentCompany->get();
        $today = new DateTimeImmutable('today');
        $year = (int) $today->format('Y');

        if (! $regime instanceof RegimeInterface || ! $company instanceof Company) {
            return [
                'profile' => $profile,
                'regime' => $regime,
                'books' => [],
                'turnover' => null,
                'limits' => [],
                'period' => null,
                'alerts' => [],
                'year' => $year,
            ];
        }

        $turnover = $this->turnoverCalculator->yearToDate($company, $profile->currencyCode, $today);

        return [
            'profile' => $profile,
            'regime' => $regime,
            'books' => $regime->books($profile),
            'turnover' => $turnover,
            'limits' => $this->limits($regime, $profile, $turnover, $today),
            'period' => $this->periodRepository->findForDate($company, $profile->declarationPeriodicity, $today),
            'alerts' => $this->alertRepository->findForYear($company, $year),
            'year' => $year,
        ];
    }

    /**
     * Every limit that has anything to measure, with how much of it is used.
     *
     * Limits on an activity the company has no turnover in are left out: a
     * services business does not need a row telling it that it has used 0% of
     * the ceiling for selling goods.
     *
     * @return list<array{threshold: Threshold, used: int}>
     */
    private function limits(
        RegimeInterface $regime,
        AccountingProfile $profile,
        TurnoverSummary $turnover,
        DateTimeImmutable $on,
    ): array {
        $limits = [];

        foreach ($regime->thresholds($profile, $on) as $threshold) {
            $amount = null === $threshold->nature
                ? $turnover->total()
                : $turnover->forNature($threshold->nature);

            if ($amount->isZero()) {
                continue;
            }

            $limits[] = [
                'threshold' => $threshold,
                // A whole percent is as fine as this reads; handed over as an
                // int so the template can compare and size a bar with it
                // instead of unwrapping a BigDecimal in Twig.
                'used' => $threshold->usageRatio($amount)
                    ->toScale(0, RoundingMode::HalfUp)
                    ->toInt(),
            ];
        }

        return $limits;
    }
}
