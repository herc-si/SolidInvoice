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

namespace Augias\DataGridBundle\Tests\GridBuilder\Column;

use Augias\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateTimeColumn::class)]
final class DateTimeColumnTest extends TestCase
{
    public function testFormat(): void
    {
        $column = DateTimeColumn::new('date');

        self::assertSame('Y-m-d H:i:s', $column->format('Y-m-d H:i:s')->getFormat());
        self::assertSame('d F Y H:i:s', $column->format('d F Y H:i:s')->getFormat());
    }
}
