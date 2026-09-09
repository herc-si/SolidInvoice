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

interface WidgetInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getData(): array;

    public function getTemplate(): string;

    /**
     * Whether this widget has anything to say in the current context.
     *
     * Checked before {@see getData()}, so a widget that cannot apply — no
     * accounting regime chosen, a feature switched off — costs no queries. It
     * also governs the picker: an unsupported widget is not offered, and is
     * skipped even when an older saved layout still names it.
     *
     * This is not the place for "the user hid it". Hiding is the layout's job,
     * and a widget that hides itself cannot be brought back.
     */
    public function supports(): bool;
}
