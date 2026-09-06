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

namespace Augias\DataGridBundle\Tests\GridBuilder\Formatter;

use Augias\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use Augias\DataGridBundle\GridBuilder\Formatter\DateTimeFormatter;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateTimeFormatter::class)]
final class DateTimeFormatterTest extends TestCase
{
    public function testFormat(): void
    {
        $formatter = new DateTimeFormatter();

        self::assertSame('2021-01-12 12:13:14', $formatter->format(DateTimeColumn::new('date'), Carbon::parse('2021-01-12 12:13:14')));
        self::assertSame('2021-01-01 00:00:00', $formatter->format(DateTimeColumn::new('date'), '2021-01-01 00:00:00'));

        self::assertSame('12 January 2021 12:13:14', $formatter->format(DateTimeColumn::new('date')->format('d F Y H:i:s'), Carbon::parse('2021-01-12 12:13:14')));
        self::assertSame('01 January 2021 00:00:00', $formatter->format(DateTimeColumn::new('date')->format('d F Y H:i:s'), '2021-01-01 00:00:00'));
    }
}
