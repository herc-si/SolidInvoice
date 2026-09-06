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

namespace Augias\Test;

use Augias\AppMode;

final class Kernel extends \Augias\Kernel
{
    public function __construct(string $environment, bool $debug)
    {
        parent::__construct(AppMode::SELF_HOSTED, $environment, $debug);
    }
}
