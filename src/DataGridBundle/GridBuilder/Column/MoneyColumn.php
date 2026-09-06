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

namespace Augias\DataGridBundle\GridBuilder\Column;

use Override;

/**
 * @see \Augias\DataGridBundle\Tests\GridBuilder\Column\MoneyColumnTest
 */
final class MoneyColumn extends Column
{
    #[Override]
    public static function new(string $field): static
    {
        return parent::new($field)
            ->cellClass('col-money');
    }
}
