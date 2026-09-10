<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\DashboardBundle\Widgets;

use Augias\SettingsBundle\SystemConfig;
use RuntimeException;

/**
 * The currency an empty stat should be denominated in.
 *
 * Every populated stat is denominated in the client's currency, which does not
 * exist when the stat has no rows behind it. The company's configured currency
 * is the only defensible basis in that case, so the tiles state it explicitly
 * instead of leaving the template to guess.
 *
 * A service rather than a trait because all four stat tiles need the same
 * answer, and a fresh install has no answer at all — the null case is worth
 * having in one place with the reasoning attached.
 *
 * @see \Augias\DashboardBundle\Tests\Widgets\DefaultCurrencyTest
 */
final readonly class DefaultCurrency
{
    public function __construct(
        private SystemConfig $systemConfig,
    ) {
    }

    /**
     * Null when no currency is configured yet (a fresh install): callers should
     * treat that as "unknown" rather than substituting an arbitrary currency.
     */
    public function code(): ?string
    {
        try {
            return $this->systemConfig->getCurrency()->getCode();
        } catch (RuntimeException) {
            return null;
        }
    }
}
