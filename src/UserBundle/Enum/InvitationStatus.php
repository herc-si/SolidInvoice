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

namespace Augias\UserBundle\Enum;

use Augias\CoreBundle\Enum\HasStatusLabel;

enum InvitationStatus: string implements HasStatusLabel
{
    case Pending = 'pending';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Expired => 'Expired',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Expired => 'red',
        };
    }
}
