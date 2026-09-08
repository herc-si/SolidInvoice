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

namespace Augias\AccountingBundle\Model;

use Augias\AccountingBundle\Entity\ThresholdAlert;

/**
 * An alert paired with the limit that produced it.
 *
 * The stored alert deliberately keeps only a stable key — a row that has to
 * survive a regime being renamed or withdrawn cannot hold a translation key
 * that may no longer exist. But whatever raised the alert has the limit in hand
 * and can say what to call it, so the two travel together as far as the
 * notification rather than the key being reverse-engineered into a label
 * somewhere downstream.
 */
final readonly class RaisedThreshold
{
    public function __construct(
        public ThresholdAlert $alert,
        public Threshold $threshold,
    ) {
    }
}
