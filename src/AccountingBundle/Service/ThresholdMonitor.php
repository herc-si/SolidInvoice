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

use Augias\AccountingBundle\Entity\ThresholdAlert;
use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Model\RaisedThreshold;
use Augias\AccountingBundle\Model\Threshold;
use Augias\AccountingBundle\Model\TurnoverSummary;
use Augias\AccountingBundle\Regime\RegimeInterface;
use Augias\AccountingBundle\Regime\RegimeRegistry;
use Augias\AccountingBundle\Repository\ThresholdAlertRepository;
use Augias\CoreBundle\Entity\Company;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Watches cumulative turnover against the limits the company's regime sets, and
 * raises an alert the first time each milestone is passed.
 *
 * Crossing one of these is not a detail: passing a VAT threshold makes a
 * micro-entrepreneur liable for VAT part-way through a year — retroactively, in
 * some cases, to the first day of the month it happened — and passing the
 * regime ceiling two years running ends the regime altogether. The figures are
 * in the books either way; the difference between a manageable event and an
 * expensive one is finding out in March rather than the following January.
 *
 * Two milestones per limit, and each is raised once per year: 80% is the
 * warning worth acting on, 100% the fact worth recording. Dropping back below
 * a limit clears nothing — annual cumulative turnover does not go down, and the
 * crossing happened.
 *
 * @see \Augias\AccountingBundle\Tests\Functional\ThresholdMonitorTest
 */
final readonly class ThresholdMonitor
{
    /**
     * Percentages of a limit worth telling someone about, checked from the
     * highest down so that a company that leaps past both in one day is told
     * about the crossing rather than about approaching it.
     *
     * @var list<int>
     */
    public const array STEPS = [100, 80];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountingProfileProvider $profileProvider,
        private RegimeRegistry $registry,
        private TurnoverCalculator $turnoverCalculator,
        private ThresholdAlertRepository $alertRepository,
    ) {
    }

    /**
     * Raises whatever is newly due for one company, and returns it.
     *
     * Nothing is raised for a company that keeps no books, and nothing is
     * raised twice — the caller runs this daily.
     *
     * @return list<RaisedThreshold>
     */
    public function check(Company $company, ?DateTimeImmutable $on = null): array
    {
        $on ??= new DateTimeImmutable('today');

        $profile = $this->profileProvider->forCompany($company);
        $regime = $this->registry->forProfile($profile);

        if (! $regime instanceof RegimeInterface) {
            return [];
        }

        $turnover = $this->turnoverCalculator->yearToDate($company, $profile->currencyCode, $on);

        if ($turnover->isEmpty()) {
            return [];
        }

        $raised = [];

        // Resolved for the date being examined, not for "now": limits move with
        // finance acts, and an alert has to quote the figure that actually
        // applied.
        foreach ($regime->thresholds($profile, $on) as $threshold) {
            $alert = $this->checkOne($company, $profile, $threshold, $turnover, $on);

            if ($alert instanceof ThresholdAlert) {
                $raised[] = new RaisedThreshold($alert, $threshold);
            }
        }

        if ([] !== $raised) {
            $this->entityManager->flush();
        }

        return $raised;
    }

    private function checkOne(
        Company $company,
        AccountingProfile $profile,
        Threshold $threshold,
        TurnoverSummary $turnover,
        DateTimeImmutable $on,
    ): ?ThresholdAlert {
        // A limit tied to an activity is measured against that activity's own
        // turnover; one that spans every activity against the lot.
        $amount = null === $threshold->nature
            ? $turnover->total()
            : $turnover->forNature($threshold->nature);

        if ($amount->isZero() || $threshold->amount->isZero()) {
            return null;
        }

        $step = $this->stepReached($threshold, $amount);
        $year = (int) $on->format('Y');

        if (null === $step || $this->alertRepository->alreadyRaised($company, $threshold->key, $year, $step)) {
            return null;
        }

        $alert = new ThresholdAlert()
            ->setThresholdKey($threshold->key)
            ->setYear($year)
            ->setStep($step)
            ->setAmount($amount)
            // Frozen: the limit may be revised in a later release, and the
            // alert has to keep saying what it was measured against.
            ->setThresholdAmount($threshold->amount)
            ->setCurrencyCode($profile->currencyCode)
            ->setTriggeredAt(new DateTimeImmutable());

        $alert->setCompany($company);

        $this->entityManager->persist($alert);

        return $alert;
    }

    /**
     * The highest milestone this turnover has reached, or null if it has
     * reached none.
     */
    private function stepReached(Threshold $threshold, BigInteger $amount): ?int
    {
        $ratio = $threshold->usageRatio($amount);

        foreach (self::STEPS as $step) {
            if ($ratio->isGreaterThanOrEqualTo($step)) {
                return $step;
            }
        }

        return null;
    }
}
