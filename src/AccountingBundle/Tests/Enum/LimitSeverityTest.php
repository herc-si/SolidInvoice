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

namespace Augias\AccountingBundle\Tests\Enum;

use Augias\AccountingBundle\Enum\LimitSeverity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LimitSeverity::class)]
final class LimitSeverityTest extends TestCase
{
    #[DataProvider('usages')]
    public function testSeverityForUsage(int $used, LimitSeverity $expected): void
    {
        self::assertSame($expected, LimitSeverity::forUsage($used));
    }

    /**
     * @return iterable<string, array{int, LimitSeverity}>
     */
    public static function usages(): iterable
    {
        yield 'untouched' => [0, LimitSeverity::Ok];
        yield 'comfortable' => [42, LimitSeverity::Ok];
        yield 'just below nearing' => [79, LimitSeverity::Ok];
        yield 'exactly at nearing' => [80, LimitSeverity::Nearing];
        yield 'just below the limit' => [99, LimitSeverity::Nearing];
        yield 'exactly at the limit' => [100, LimitSeverity::Exceeded];
        yield 'well past it' => [143, LimitSeverity::Exceeded];
    }
}
