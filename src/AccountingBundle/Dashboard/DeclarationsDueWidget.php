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

namespace Augias\AccountingBundle\Dashboard;

use Augias\AccountingBundle\Regime\RegimeInterface;
use Augias\AccountingBundle\Regime\RegimeRegistry;
use Augias\AccountingBundle\Service\AccountingProfileProvider;
use Augias\AccountingBundle\Service\CurrentCompany;
use Augias\AccountingBundle\Service\PendingDeclarationFinder;
use Augias\CoreBundle\Entity\Company;
use Augias\DashboardBundle\Attribute\AsDashboardWidget;
use Augias\DashboardBundle\Enum\WidgetWidth;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Widgets\WidgetInterface;
use RuntimeException;

/**
 * Periods that have ended and have not been declared.
 *
 * A quarter that was closed and then forgotten is the thing the declarations
 * page exists to make visible, and it is also the thing nobody goes to that
 * page to look for. Late filing carries penalties that grow with the delay, so
 * this is the one accounting fact worth putting in front of someone unasked.
 *
 * It reports how long a period has been waiting, never a deadline. Filing
 * deadlines depend on the collecting body, the periodicity and the year, none
 * of which is modelled or verified here; a date on this card would be one the
 * user might plan around. "Ended 47 days ago" is something Augias knows.
 *
 * A quarter in which nothing was received has no period row at all, and is
 * listed here as one to create — that is the quarter most easily forgotten, and
 * a regime may still want a nil return for it.
 *
 * @see \Augias\AccountingBundle\Tests\Dashboard\DeclarationsDueWidgetTest
 */
#[AsDashboardWidget(
    id: 'accounting_declarations',
    label: 'dashboard.widget.accounting_declarations',
    icon: 'tabler:file-report',
    zone: WidgetZone::LeftColumn,
    // Between the ceilings above it and this week's invoices below: a missed
    // filing is more urgent than a ceiling being approached and less urgent
    // than nothing, but it is rarer than either — so it sits where it will be
    // noticed on the days it has anything to say.
    priority: 125,
    width: WidgetWidth::Full,
)]
final readonly class DeclarationsDueWidget implements WidgetInterface
{
    /**
     * A card, not a page. The declarations screen lists every period of the
     * year; this shows the ones that have been waiting longest and links there.
     */
    private const int PERIODS_SHOWN = 4;

    public function __construct(
        private AccountingProfileProvider $profileProvider,
        private RegimeRegistry $registry,
        private CurrentCompany $currentCompany,
        private PendingDeclarationFinder $finder,
    ) {
    }

    public function supports(): bool
    {
        $profile = $this->profileProvider->forCompany();

        return $profile->isConfigured()
            && $this->registry->forProfile($profile) instanceof RegimeInterface
            && $this->company() instanceof Company;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $profile = $this->profileProvider->forCompany();
        $regime = $this->registry->forProfile($profile);
        $company = $this->company();

        if (! $regime instanceof RegimeInterface || ! $company instanceof Company) {
            return ['regime' => null, 'pending' => [], 'filingUrl' => null];
        }

        return [
            'regime' => $regime,
            'pending' => $this->finder->find($company, $profile, self::PERIODS_SHOWN),
            // Named on the card so it is unambiguous that Augias computed the
            // figures and filed nothing.
            'filingUrl' => $regime->filingUrl(),
        ];
    }

    public function getTemplate(): string
    {
        return '@AugiasAccounting/Widget/declarations_due.html.twig';
    }

    /**
     * Called from supports(), where an exception would take the whole dashboard
     * down for a card the user may not even have.
     */
    private function company(): ?Company
    {
        try {
            return $this->currentCompany->get();
        } catch (RuntimeException) {
            return null;
        }
    }
}
