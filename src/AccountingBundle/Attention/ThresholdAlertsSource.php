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

namespace Augias\AccountingBundle\Attention;

use Augias\AccountingBundle\Entity\ThresholdAlert;
use Augias\AccountingBundle\Model\Threshold;
use Augias\AccountingBundle\Regime\RegimeInterface;
use Augias\AccountingBundle\Regime\RegimeRegistry;
use Augias\AccountingBundle\Repository\ThresholdAlertRepository;
use Augias\AccountingBundle\Service\AccountingProfileProvider;
use Augias\AccountingBundle\Service\CurrentCompany;
use Augias\CoreBundle\Entity\Company;
use Augias\DashboardBundle\Attention\AttentionSourceInterface;
use DateTimeImmutable;
use RuntimeException;
use function array_slice;
use function usort;

/**
 * Limits this company has already crossed, in the card where a user looks for
 * what is waiting for them.
 *
 * ThresholdMonitor raises each milestone once and emails about it. An email is
 * a one-off that gets archived; the consequence is not. Passing a VAT threshold
 * makes a micro-entrepreneur liable part-way through the year, and it stays
 * true for the rest of it — so the crossing belongs somewhere it keeps being
 * visible, next to the overdue invoices rather than in a card of its own that
 * would be empty most of the year.
 *
 * A section rather than a widget, because that was the choice: this is one more
 * kind of thing waiting for the user, not one more thing to arrange.
 *
 * @see \Augias\AccountingBundle\Tests\Attention\ThresholdAlertsSourceTest
 */
final class ThresholdAlertsSource implements AttentionSourceInterface
{
    /**
     * The card has room for the crossings that changed something, not for a
     * year's worth of milestones.
     */
    private const int ALERTS_SHOWN = 3;

    /**
     * @var list<ThresholdAlert>|null
     */
    private ?array $alerts = null;

    public function __construct(
        private readonly AccountingProfileProvider $profileProvider,
        private readonly RegimeRegistry $registry,
        private readonly CurrentCompany $currentCompany,
        private readonly ThresholdAlertRepository $alertRepository,
    ) {
    }

    public function supports(): bool
    {
        $profile = $this->profileProvider->forCompany();

        return $profile->isConfigured()
            && $this->registry->forProfile($profile) instanceof RegimeInterface
            && $this->company() instanceof Company;
    }

    public function hasItems(): bool
    {
        return [] !== $this->raisedThisYear();
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $alerts = $this->raisedThisYear();

        // Worst first, then most recent. A company that passed a limit outright
        // does not want to read about the one it is merely approaching first.
        usort($alerts, static fn (ThresholdAlert $a, ThresholdAlert $b): int
            => [$b->getStep(), $b->getTriggeredAt()] <=> [$a->getStep(), $a->getTriggeredAt()]);

        return [
            'alerts' => array_slice($alerts, 0, self::ALERTS_SHOWN),
            'alertsTotal' => count($alerts),
            'thresholdLabels' => $this->labels(),
        ];
    }

    public function getTemplate(): string
    {
        return '@AugiasAccounting/Widget/_attention_thresholds.html.twig';
    }

    /**
     * Translation key per threshold key, taken from the regime that defines
     * them.
     *
     * An alert only stores the key it was raised against, and the mapping from
     * that to a name is not a string transformation — `vat_franchise.base.x`
     * is named by `accounting.threshold.vat_franchise_base`. Asking the regime
     * is the only version that cannot drift. A key the regime no longer defines
     * simply has no label, and the row shows the figures without a name rather
     * than disappearing.
     *
     * @return array<string, string>
     */
    private function labels(): array
    {
        $profile = $this->profileProvider->forCompany();
        $regime = $this->registry->forProfile($profile);

        if (! $regime instanceof RegimeInterface) {
            return [];
        }

        $labels = [];

        foreach ($regime->thresholds($profile, new DateTimeImmutable('today')) as $threshold) {
            /** @var Threshold $threshold */
            $labels[$threshold->key] = $threshold->labelKey;
        }

        return $labels;
    }

    /**
     * Memoised: hasItems() and getData() are both called for every render, and
     * they are asking the same question.
     *
     * @return list<ThresholdAlert>
     */
    private function raisedThisYear(): array
    {
        if (null !== $this->alerts) {
            return $this->alerts;
        }

        $company = $this->company();

        if (! $company instanceof Company) {
            return $this->alerts = [];
        }

        return $this->alerts = $this->alertRepository->findForYear(
            $company,
            (int) new DateTimeImmutable('today')->format('Y'),
        );
    }

    private function company(): ?Company
    {
        try {
            return $this->currentCompany->get();
        } catch (RuntimeException) {
            return null;
        }
    }
}
