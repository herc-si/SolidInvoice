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
use Augias\AccountingBundle\Model\DeclarationLine;
use Augias\AccountingBundle\Regime\RegimeInterface;
use Augias\AccountingBundle\Regime\RegimeRegistry;
use Augias\AccountingBundle\Service\AccountingProfileProvider;
use Augias\AccountingBundle\Service\DeclarationBuilder;
use DateTimeImmutable;
use Symfony\Bridge\Twig\Attribute\Template;
use function array_map;

/**
 * One period's declaration, itemised.
 *
 * The figures are shown the way they have to be typed into the collecting
 * body's form — one line per charge, each with the base it was applied to and
 * the rate that produced it — rather than as a single amount due. A user who
 * cannot see which rate was used cannot check it, and these rates are not
 * verified against any official source.
 */
final readonly class View
{
    public function __construct(
        private DeclarationBuilder $builder,
        private AccountingProfileProvider $profileProvider,
        private RegimeRegistry $registry,
    ) {
    }

    /**
     * @return array{
     *     period: AccountingPeriod,
     *     returns: list<array{declaration: Declaration, lines: list<DeclarationLine>}>,
     *     regime: RegimeInterface|null,
     *     periodHasEnded: bool
     * }
     */
    #[Template('@AugiasAccounting/Declaration/view.html.twig')]
    public function __invoke(AccountingPeriod $period): array
    {
        $profile = $this->profileProvider->forCompany($period->getCompany());
        $returns = [];

        // Every return the period owes, on one page. A quarter is a quarter
        // whichever form it is being declared on, and splitting them across two
        // URLs would make the user hunt for the second.
        foreach ($this->builder->kindsOwed($profile, $period) as $kind) {
            $declaration = $this->builder->forPeriod($period, $kind);

            $returns[] = [
                'declaration' => $declaration,
                // Read back from what was stored rather than recomputed for
                // display: a submitted declaration must show what was filed, and
                // a draft must show the figures it was last saved with.
                'lines' => array_map(DeclarationLine::fromArray(...), $declaration->getLines()),
            ];
        }

        return [
            'period' => $period,
            'returns' => $returns,
            'regime' => $this->registry->forProfile($profile),
            // Closing lives on this page rather than the home page: the home
            // page only ever shows the period today falls in, which by the rule
            // in AccountingPeriodManager::close() can never be sealed yet. This
            // is the page that is reached per period, and the one already
            // explaining why the figures are not final.
            'periodHasEnded' => $period->getEndDate() < new DateTimeImmutable('today'),
        ];
    }
}
