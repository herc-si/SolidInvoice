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

namespace Augias\AccountingBundle\Tests\Service;

use Augias\AccountingBundle\AccountingSettings;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Service\AccountingProfileProvider;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\SettingsBundle\SystemConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Settings can be missing, empty, or hold something no longer valid — a regime
 * that was withdrawn, an enum case that was renamed. None of that may take the
 * books down, so every value falls back and the caller decides what to do about
 * a profile that reports itself unconfigured.
 */
#[CoversClass(AccountingProfileProvider::class)]
final class AccountingProfileProviderTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testACompanyThatHasChosenNothingReportsItselfUnconfigured(): void
    {
        self::assertFalse($this->provider()->forCompany($this->company)->isConfigured());
    }

    public function testAnEmptyRegimeIsTreatedAsNoRegimeRatherThanAnEmptyOne(): void
    {
        $this->config()->set(AccountingSettings::REGIME, '   ');

        self::assertFalse($this->provider()->forCompany($this->company)->isConfigured());
    }

    public function testAnActivityNatureThatNoLongerExistsFallsBackToServices(): void
    {
        $this->config()->set(AccountingSettings::PRIMARY_ACTIVITY, 'something_removed');

        self::assertSame(ActivityNature::ServicesBnc, $this->provider()->forCompany($this->company)->primaryActivity);
    }

    /**
     * Quarterly is what a French micro-entrepreneur gets by default when they
     * do not opt for monthly — and yearly is not on offer at all, so a stored
     * "year" is not honoured either.
     */
    public function testTheDeclarationPeriodicityFallsBackToQuarterly(): void
    {
        $this->config()->set(AccountingSettings::DECLARATION_PERIODICITY, PeriodType::Year->value);

        self::assertSame(PeriodType::Quarter, $this->provider()->forCompany($this->company)->declarationPeriodicity);
    }

    public function testAnUnreadableStartDateReadsAsNoStartDate(): void
    {
        $this->config()->set(AccountingSettings::ACTIVITY_START_DATE, 'not a date at all');

        self::assertNull($this->provider()->forCompany($this->company)->activityStartDate);
    }

    /**
     * An unchecked checkbox is stored as the string '0', which is truthy as a
     * non-empty string — the comparison has to be explicit.
     */
    public function testAnUncheckedCheckboxReadsAsFalse(): void
    {
        $this->config()->set(AccountingSettings::VAT_EXEMPT, '0');

        self::assertFalse($this->provider()->forCompany($this->company)->vatExempt);
    }

    public function testRegimeSpecificSettingsTravelInTheirOwnBag(): void
    {
        $this->config()->set(AccountingSettings::FR_PENSION_FUND, 'cipav');
        $this->config()->set(AccountingSettings::FR_ACRE, '1');

        $profile = $this->provider()->forCompany($this->company);

        self::assertSame('cipav', $profile->option('pension_fund'));
        self::assertTrue($profile->boolOption('acre'));
        self::assertFalse($profile->boolOption('income_tax_option'));
    }

    private function provider(): AccountingProfileProvider
    {
        return self::getContainer()->get(AccountingProfileProvider::class);
    }

    private function config(): SystemConfig
    {
        return self::getContainer()->get(SystemConfig::class);
    }
}
