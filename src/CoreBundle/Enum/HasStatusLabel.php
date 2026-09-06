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

namespace Augias\CoreBundle\Enum;

interface HasStatusLabel
{
    /**
     * Returns a human-friendly label for display on the frontend.
     */
    public function getLabel(): string;

    /**
     * Returns the Tabler badge color (e.g. 'green', 'red', 'yellow').
     */
    public function getColor(): string;
}
