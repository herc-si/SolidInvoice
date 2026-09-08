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

namespace Augias\InvoiceBundle\Tests\Config;

use Augias\CoreBundle\Generator\BillingIdGenerator;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\InvoiceBundle\Config\ConfigProvider;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\SettingsBundle\SystemConfig;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * What a company gets out of the box, exercised through the generator rather
 * than by reading the seeded strings back: the defaults are only worth anything
 * if they produce the number they promise.
 */
#[CoversClass(ConfigProvider::class)]
#[CoversClass(BillingIdGenerator::class)]
final class ConfigProviderTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testANewCompanyNumbersItsInvoicesFactAndTheYear(): void
    {
        $year = new DateTimeImmutable()->format('Y');

        self::assertSame(
            'FACT-1-' . $year,
            self::getContainer()->get(BillingIdGenerator::class)->generate(new Invoice(), ['field' => 'invoiceId']),
        );
    }

    /**
     * The year is stored as a placeholder, not as the four digits it resolves
     * to — otherwise every invoice raised after 31 December would carry the
     * year before.
     */
    public function testTheYearIsStoredAsAPlaceholder(): void
    {
        self::assertSame('-{year}', self::getContainer()->get(SystemConfig::class)->get('invoice/id_generation/id_suffix'));
    }

    /**
     * Quotes are left numbered as they were.
     */
    public function testQuoteNumberingIsUnchanged(): void
    {
        $config = self::getContainer()->get(SystemConfig::class);

        self::assertSame('', $config->get('quote/id_generation/id_prefix'));
        self::assertSame('', $config->get('quote/id_generation/id_suffix'));
    }
}
