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

interface LayoutProviderInterface
{
    /**
     * The arrangement to render for whoever is asking, right now.
     */
    public function currentLayout(): ResolvedLayout;
}
