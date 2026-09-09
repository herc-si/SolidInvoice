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
 * Where on the dashboard a widget can sit.
 *
 * Declaration order is the order the zones stack on a narrow screen, and the
 * order a reconciled layout walks them in, so it is not arbitrary: the full
 * width band comes first, then the wide column, then the narrow one.
 */
enum WidgetZone: string
{
    case Top = 'top';
    case LeftColumn = 'left_column';
    case RightColumn = 'right_column';

    public static function tryFromName(?string $zone): ?self
    {
        return null === $zone ? null : self::tryFrom($zone);
    }
}
