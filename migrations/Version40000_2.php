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

namespace DoctrineMigrations;

use Augias\AccountingBundle\AccountingSettings;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Form\Type\ActivityNatureChoiceType;
use Augias\AccountingBundle\Form\Type\DeclarationPeriodicityChoiceType;
use Augias\AccountingBundle\Form\Type\PensionFundChoiceType;
use Augias\AccountingBundle\Form\Type\RegimeChoiceType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Uid\Ulid;
use function json_encode;

/**
 * Seeds the accounting settings for companies that already exist.
 *
 * New companies get these from
 * {@see \Augias\AccountingBundle\Config\AccountingConfigProvider} when they are
 * created; this covers everyone else, because
 * {@see \Augias\SettingsBundle\Repository\SettingsRepository::store()} only ever
 * updates rows and never inserts one.
 *
 * The rows are spelled out here rather than read back from the provider on
 * purpose: a migration is a record of what was applied at a point in time, and
 * calling the provider would make this file's behaviour change every time a new
 * setting is added to it. A setting added later needs its own migration.
 */
final class Version40000_2 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed the accounting settings for existing companies (new companies get them from AccountingBundle\Config\AccountingConfigProvider)';
    }

    public function up(Schema $schema): void
    {
        // Data only — see postUp(). Declared so the migration is not reported
        // as empty.
    }

    public function down(Schema $schema): void
    {
        // Data only — see postDown().
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $companies = $this->connection->fetchAllAssociative('SELECT id FROM companies');

        foreach ($companies as $company) {
            foreach ($this->settings() as $setting) {
                $exists = $this->connection->fetchOne(
                    'SELECT 1 FROM app_config WHERE company_id = ? AND setting_key = ?',
                    [$company['id'], $setting['key']],
                );

                if ($exists !== false) {
                    continue;
                }

                $this->connection->insert('app_config', [
                    'id' => new Ulid()->toBinary(),
                    'company_id' => $company['id'],
                    'setting_key' => $setting['key'],
                    'setting_value' => $setting['value'],
                    'description' => $setting['description'],
                    'field_type' => $setting['type'],
                    // Every option the provider passes, not just the label: a
                    // DateType that is not told `input: string` cannot read
                    // back the plain string a setting round-trips as.
                    'form_options' => json_encode($setting['options']),
                    'default_value' => $setting['value'],
                ]);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        foreach ($this->settings() as $setting) {
            $this->connection->delete('app_config', ['setting_key' => $setting['key']]);
        }
    }

    /**
     * Order matters: settings are read back ordered by their ULID, so this is
     * the order the fields appear on the settings tab.
     *
     * @return list<array{key: string, value: string|null, description: string, type: string, options: array<string, string>}>
     */
    private function settings(): array
    {
        return [
            [
                'key' => AccountingSettings::REGIME,
                'value' => null,
                'description' => 'accounting.settings.regime.description',
                'type' => RegimeChoiceType::class,
                'options' => ['label' => 'accounting.settings.regime.label'],
            ],
            [
                'key' => AccountingSettings::PRIMARY_ACTIVITY,
                'value' => ActivityNature::ServicesBnc->value,
                'description' => 'accounting.settings.primary_activity.description',
                'type' => ActivityNatureChoiceType::class,
                'options' => ['label' => 'accounting.settings.primary_activity.label'],
            ],
            [
                'key' => AccountingSettings::ACTIVITY_START_DATE,
                'value' => null,
                'description' => 'accounting.settings.activity_start_date.description',
                'type' => DateType::class,
                'options' => [
                    'label' => 'accounting.settings.activity_start_date.label',
                    // Settings round-trip as plain strings, so the field has to
                    // read and write one rather than a DateTime object.
                    'input' => 'string',
                    'widget' => 'single_text',
                ],
            ],
            [
                'key' => AccountingSettings::DECLARATION_PERIODICITY,
                'value' => PeriodType::Quarter->value,
                'description' => 'accounting.settings.declaration_periodicity.description',
                'type' => DeclarationPeriodicityChoiceType::class,
                'options' => ['label' => 'accounting.settings.declaration_periodicity.label'],
            ],
            [
                'key' => AccountingSettings::VAT_EXEMPT,
                'value' => '0',
                'description' => 'accounting.settings.vat_exempt.description',
                'type' => CheckboxType::class,
                'options' => ['label' => 'accounting.settings.vat_exempt.label'],
            ],
            [
                'key' => AccountingSettings::VAT_EXEMPT_MENTION,
                'value' => AccountingSettings::DEFAULT_VAT_EXEMPT_MENTION,
                'description' => 'accounting.settings.vat_exempt_mention.description',
                'type' => TextType::class,
                'options' => ['label' => 'accounting.settings.vat_exempt_mention.label'],
            ],
            [
                'key' => AccountingSettings::FR_INCOME_TAX_OPTION,
                'value' => '0',
                'description' => 'accounting.settings.income_tax_option.description',
                'type' => CheckboxType::class,
                'options' => ['label' => 'accounting.settings.income_tax_option.label'],
            ],
            [
                'key' => AccountingSettings::FR_ACRE,
                'value' => '0',
                'description' => 'accounting.settings.acre.description',
                'type' => CheckboxType::class,
                'options' => ['label' => 'accounting.settings.acre.label'],
            ],
            [
                'key' => AccountingSettings::FR_PENSION_FUND,
                'value' => null,
                'description' => 'accounting.settings.pension_fund.description',
                'type' => PensionFundChoiceType::class,
                'options' => ['label' => 'accounting.settings.pension_fund.label'],
            ],
        ];
    }
}
