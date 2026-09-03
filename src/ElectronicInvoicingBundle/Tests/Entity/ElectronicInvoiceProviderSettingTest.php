<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ElectronicInvoicingBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;

#[CoversClass(ElectronicInvoiceProviderSetting::class)]
final class ElectronicInvoiceProviderSettingTest extends TestCase
{
    public function testGetIdReturnsNullForNewEntity(): void
    {
        $setting = new ElectronicInvoiceProviderSetting();

        self::assertNull($setting->getId());
    }

    public function testSetAndGetName(): void
    {
        $setting = new ElectronicInvoiceProviderSetting();
        $setting->setName('My Provider');

        self::assertSame('My Provider', $setting->getName());
    }

    public function testSetAndGetProvider(): void
    {
        $setting = new ElectronicInvoiceProviderSetting();
        $setting->setProvider('test_provider');

        self::assertSame('test_provider', $setting->getProvider());
    }

    public function testSetAndGetSettings(): void
    {
        $setting = new ElectronicInvoiceProviderSetting();
        $config = ['reference_prefix' => 'ACME'];
        $setting->setSettings($config);

        self::assertSame($config, $setting->getSettings());
    }

    public function testDefaultSettingsAreEmpty(): void
    {
        $setting = new ElectronicInvoiceProviderSetting();

        self::assertSame([], $setting->getSettings());
    }

    public function testActiveDefaultsToFalse(): void
    {
        $setting = new ElectronicInvoiceProviderSetting();

        self::assertFalse($setting->isActive());
    }

    public function testSetAndGetActive(): void
    {
        $setting = new ElectronicInvoiceProviderSetting();
        $setting->setActive(true);

        self::assertTrue($setting->isActive());
    }

    public function testToString(): void
    {
        $setting = new ElectronicInvoiceProviderSetting();
        $setting->setName('My Provider');

        self::assertSame('My Provider', (string) $setting);
    }
}
