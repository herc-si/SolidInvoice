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

namespace Augias\CoreBundle\Event;

use Augias\CoreBundle\Entity\Company;
use Symfony\Contracts\EventDispatcher\Event;

final class CompanyCreatedEvent extends Event
{
    public function __construct(
        public readonly Company $company
    ) {
    }
}
