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

namespace Augias\CoreBundle\Tests\Doctrine\Listener;

use Augias\CoreBundle\Test\Factory\CompanyFactory;
use Augias\SettingsBundle\SystemConfig;
use DateTimeInterface;
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
 * @see \Augias\CoreBundle\Doctrine\Listener\CompanyCreatedListener
 */
final class CompanyCreatedListenerTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['AUGIAS_LOCALE'] = $_ENV['AUGIAS_LOCALE'] = 'en_US';
        $_SERVER['AUGIAS_INSTALLED'] = $_ENV['AUGIAS_INSTALLED'] = date(DateTimeInterface::ATOM);
        putenv('AUGIAS_INSTALLED=' . $_SERVER['AUGIAS_INSTALLED']);
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['AUGIAS_LOCALE'],
            $_ENV['AUGIAS_LOCALE'],
            $_SERVER['AUGIAS_INSTALLED'],
            $_ENV['AUGIAS_INSTALLED'],
        );
        putenv('AUGIAS_INSTALLED');

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
