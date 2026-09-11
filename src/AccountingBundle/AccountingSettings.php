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

namespace Augias\AccountingBundle;

use Augias\SettingsBundle\SystemConfig;

/**
 * The setting paths this bundle owns.
 *
 * They are kept here rather than on {@see \Augias\SettingsBundle\SystemConfig}
 * — where a handful of cross-cutting paths live — because they are numerous and
 * belong to one bundle, the same way
 * {@see \Augias\CoreBundle\Templates\BillingTemplateResolver::TEMPLATE_SETTING_KEY}
 * sits with its own resolver.
 *
 * The `accounting/` prefix is not cosmetic: the settings screen derives its tabs
 * from the first segment of each path, so this prefix is what puts these fields
 * on their own tab.
 *
 * Any path added here must ALSO be seeded by a migration for companies that
 * already exist — {@see \Augias\SettingsBundle\Repository\SettingsRepository::store()}
 * only ever updates, never inserts.
 */
final class AccountingSettings
{
    /** Which regime the company is on; empty until the user picks one. */
    final public const string REGIME = 'accounting/regime';

    /**
     * Outside the scope of VAT — franchise en base for a French micro-entreprise.
     *
     * Aliases the constant on SystemConfig rather than repeating the literal:
     * the billing side reads this on every document it renders and cannot
     * depend on this bundle to find the path.
     */
    final public const string VAT_EXEMPT = SystemConfig::VAT_EXEMPT_CONFIG_PATH;

    /** The legal wording printed on invoices and quotes when VAT-exempt. */
    final public const string VAT_EXEMPT_MENTION = SystemConfig::VAT_EXEMPT_MENTION_CONFIG_PATH;

    /** Needed to scale a first, partial year's limits down pro rata. */
    final public const string ACTIVITY_START_DATE = 'accounting/activity_start_date';

    /** Default activity nature for automatically created revenue entries. */
    final public const string PRIMARY_ACTIVITY = 'accounting/primary_activity';

    /** Monthly or quarterly — also the granularity the books are closed at. */
    final public const string DECLARATION_PERIODICITY = 'accounting/declaration_periodicity';

    /**
     * Books up to and including this date are shut: entries filed into a period
     * that ended by then can no longer be changed or removed.
     *
     * The cran between "still being kept" and "sealed for good". Sealing is
     * one-way and happens per period; this is the reversible control for the
     * span in between — the weeks between a period ending and anyone getting
     * round to closing it, and the ordinary "I have declared that month, leave
     * it alone" of a company that files VAT.
     *
     * Never read on its own: {@see \Augias\AccountingBundle\Service\LedgerLockDate}
     * takes it as a floor and raises it to the end of the last sealed period,
     * so it cannot be set back to before what is already final.
     */
    final public const string LOCK_DATE = 'accounting/lock_date';

    /** Versement libératoire de l'impôt sur le revenu. */
    final public const string FR_INCOME_TAX_OPTION = 'accounting/fr_micro/income_tax_option';

    /** Aide aux créateurs et repreneurs d'entreprise — a reduced contribution rate. */
    final public const string FR_ACRE = 'accounting/fr_micro/acre';

    /** SSI or CIPAV; they charge different rates on the same BNC turnover. */
    final public const string FR_PENSION_FUND = 'accounting/fr_micro/pension_fund';

    /**
     * Default wording for a French micro-entreprise in franchise en base. The
     * article reference is mandatory on the invoice, which is why it is spelled
     * out rather than left to the user to remember.
     */
    final public const string DEFAULT_VAT_EXEMPT_MENTION = SystemConfig::DEFAULT_VAT_EXEMPT_MENTION;

    /**
     * The regime-specific paths, mapped to the short keys
     * {@see \Augias\AccountingBundle\Model\AccountingProfile::option()} exposes
     * them under.
     *
     * @return array<string, string> short key => full setting path
     */
    public static function regimeOptionPaths(): array
    {
        return [
            'income_tax_option' => self::FR_INCOME_TAX_OPTION,
            'acre' => self::FR_ACRE,
            'pension_fund' => self::FR_PENSION_FUND,
        ];
    }
}
