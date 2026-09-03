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

namespace SolidInvoice\CoreBundle\Tests\Doctrine\Listener;

use DateTimeInterface;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function date;
use function putenv;

/**
 * Locks in the fallback half of the fix for a company's default locale
 * being silently seeded as English regardless of the locale chosen at
 * install: outside of a request (eg. a CLI install, or this test), a newly
 * created company still defaults its locale setting to English rather than
 * erroring or leaving it unset.
 *
 * @see \SolidInvoice\CoreBundle\Doctrine\Listener\CompanyCreatedListener
 */
final class CompanyCreatedListenerTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['SOLIDINVOICE_LOCALE'] = $_ENV['SOLIDINVOICE_LOCALE'] = 'en_US';
        $_SERVER['SOLIDINVOICE_INSTALLED'] = $_ENV['SOLIDINVOICE_INSTALLED'] = date(DateTimeInterface::ATOM);
        putenv('SOLIDINVOICE_INSTALLED=' . $_SERVER['SOLIDINVOICE_INSTALLED']);
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['SOLIDINVOICE_LOCALE'],
            $_ENV['SOLIDINVOICE_LOCALE'],
            $_SERVER['SOLIDINVOICE_INSTALLED'],
            $_ENV['SOLIDINVOICE_INSTALLED'],
        );
        putenv('SOLIDINVOICE_INSTALLED');

        parent::tearDown();
    }

    public function testCompanyLocaleSettingFallsBackToDefaultWithoutARequest(): void
    {
        self::bootKernel();

        CompanyFactory::createOne(['currency' => 'EUR']);

        $systemConfig = static::getContainer()->get(SystemConfig::class);

        self::assertSame('en', $systemConfig->get(SystemConfig::LOCALE_CONFIG_PATH));
    }
}
