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

namespace Augias\DataGridBundle\GridBuilder\Formatter;

use Augias\DataGridBundle\GridBuilder\Column\Column;
use Symfony\Component\Translation\TranslatableMessage;

interface FormatterInterface
{
    public function format(Column $column, mixed $value): string | TranslatableMessage;
}
