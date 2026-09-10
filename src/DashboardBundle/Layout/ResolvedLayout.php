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

namespace Augias\DashboardBundle\Layout;

use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Widgets\WidgetDefinition;

/**
 * What actually gets rendered this request: the stored layout reconciled with
 * the widgets that exist and apply right now.
 *
 * @see \Augias\DashboardBundle\Tests\Layout\LayoutResolverTest
 */
final readonly class ResolvedLayout
{
    /**
     * @param array<string, list<WidgetDefinition>> $zones  keyed by {@see WidgetZone::$value}
     * @param list<WidgetDefinition>                $hidden removable widgets the user can put back
     */
    public function __construct(
        public array $zones,
        public array $hidden = [],
    ) {
    }

    /**
     * @return list<WidgetDefinition>
     */
    public function zone(WidgetZone $zone): array
    {
        return $this->zones[$zone->value] ?? [];
    }
}
