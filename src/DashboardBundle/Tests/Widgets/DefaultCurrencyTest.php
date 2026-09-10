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

namespace Augias\DashboardBundle\Tests\Widgets;

use Augias\DashboardBundle\Widgets\DefaultCurrency;
use Augias\SettingsBundle\SystemConfig;
use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(DefaultCurrency::class)]
final class DefaultCurrencyTest extends TestCase
{
    public function testReturnsTheConfiguredCurrency(): void
    {
        $systemConfig = $this->createStub(SystemConfig::class);
        $systemConfig->method('getCurrency')
            ->willReturn(new Currency('EUR'));

        self::assertSame('EUR', new DefaultCurrency($systemConfig)->code());
    }

    /**
     * A fresh install has no currency configured, and SystemConfig says so by
     * throwing. Null is the honest answer: substituting a currency of our own
     * would denominate an empty stat in one the company does not use.
     */
    public function testReturnsNullWhenNothingIsConfiguredYet(): void
    {
        $systemConfig = $this->createStub(SystemConfig::class);
        $systemConfig->method('getCurrency')
            ->willThrowException(new RuntimeException('no currency configured'));

        self::assertNull(new DefaultCurrency($systemConfig)->code());
    }
}
