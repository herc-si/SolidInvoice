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

namespace Augias\DashboardBundle\Enum;

/**
 * How much of its zone a widget takes up.
 *
 * A vocabulary of five named fractions rather than a track count, because the
 * geometry belongs to the stylesheet: the enum says "half", `_dashboard.scss`
 * decides what half means at each breakpoint, and neither the stored layout nor
 * the JavaScript ever names a number of columns. Adding a breakpoint is then a
 * stylesheet change and nothing else.
 *
 * Declaration order is narrow to wide, and {@see cases()} is what feeds the
 * widen/narrow buttons — so the order is the control's behaviour, not a
 * cosmetic choice.
 */
enum WidgetWidth: string
{
    case Quarter = 'quarter';
    case Third = 'third';
    case Half = 'half';
    case TwoThirds = 'two_thirds';
    case Full = 'full';

    public static function tryFromName(?string $width): ?self
    {
        return null === $width ? null : self::tryFrom($width);
    }

    /**
     * The vocabulary, narrow first, for whoever offers the choice.
     *
     * The template hands this to the Stimulus controller so the client has no
     * list of its own to keep in step with this one.
     *
     * @return list<string>
     */
    public static function vocabulary(): array
    {
        return array_column(self::cases(), 'value');
    }
}
