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

use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Model\ThresholdSet;
use DateTimeImmutable;

/**
 * Supplies the turnover limits that apply to a company on a given date.
 *
 * Resolution is by date, not by "current", because a period being closed or a
 * declaration being reviewed has to be measured against the limits that were in
 * force then — limits move with finance acts, sometimes retroactively.
 */
interface ThresholdProviderInterface
{
    /**
     * @param DateTimeImmutable $on the date the limits are resolved for,
     *                              usually the end of the period being examined
     */
    public function thresholds(AccountingProfile $profile, DateTimeImmutable $on): ThresholdSet;
}
