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

namespace Augias\AccountingBundle\Regime;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Model\DeclarationResult;
use Augias\AccountingBundle\Model\TurnoverSummary;

/**
 * Turns a period's turnover into the charges owed on it.
 *
 * Implementations must itemise: one {@see \Augias\AccountingBundle\Model\DeclarationLine}
 * per charge, each carrying the base and the rate it came from. The user has to
 * transcribe these figures into the collecting body's own form box by box, and
 * has to be able to tell — and correct — which published rate was applied.
 */
interface ContributionCalculatorInterface
{
    public function calculate(
        TurnoverSummary $turnover,
        AccountingProfile $profile,
        AccountingPeriod $period,
    ): DeclarationResult;
}
