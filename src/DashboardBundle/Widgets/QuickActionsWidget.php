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

use Augias\DashboardBundle\Attribute\AsDashboardWidget;
use Augias\DashboardBundle\Enum\WidgetZone;

/**
 * @see \Augias\DashboardBundle\Tests\Widgets\QuickActionsWidgetTest
 */
#[AsDashboardWidget(
    id: 'quick_actions',
    label: 'dashboard.widget.quick_actions',
    icon: 'tabler:bolt',
    zone: WidgetZone::RightColumn,
    priority: 110,
)]
final class QuickActionsWidget implements WidgetInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return [];
    }

    /**
     * Pure links, nothing to check.
     */
    public function supports(): bool
    {
        return true;
    }

    public function getTemplate(): string
    {
        return '@AugiasDashboard/Widget/quick_actions.html.twig';
    }
}
