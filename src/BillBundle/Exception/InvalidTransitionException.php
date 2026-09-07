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

namespace Augias\BillBundle\Exception;

use Exception;

class InvalidTransitionException extends Exception
{
    public function __construct(string $transition)
    {
        parent::__construct('bill.transition.exception.' . $transition);
    }
}
