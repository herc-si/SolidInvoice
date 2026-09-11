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
use Augias\AccountingBundle\Form\Type\FiscalYearStartMonthChoiceType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Ulid;
use function json_encode;

/**
 * Seeds the month the financial year opens on, for companies that already
 * exist.
 *
 * January, which is what every period created so far assumed and what a French
 * micro-entrepreneur is held to anyway. Seeding anything else would silently
 * move the boundaries of periods that have already been sealed.
 */
final class Version40000_11 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed the financial year start month for existing companies';
    }

    public function up(Schema $schema): void
    {
        // Data only — see postUp().
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
        foreach ($this->connection->fetchAllAssociative('SELECT id FROM companies') as $company) {
            $exists = $this->connection->fetchOne(
                'SELECT 1 FROM app_config WHERE company_id = ? AND setting_key = ?',
                [$company['id'], AccountingSettings::FISCAL_YEAR_START_MONTH],
            );

            if ($exists !== false) {
                continue;
            }

            $this->connection->insert('app_config', [
                'id' => new Ulid()->toBinary(),
                'company_id' => $company['id'],
                'setting_key' => AccountingSettings::FISCAL_YEAR_START_MONTH,
                'setting_value' => '1',
                'description' => 'accounting.settings.fiscal_year_start_month.description',
                'field_type' => FiscalYearStartMonthChoiceType::class,
                'form_options' => json_encode(['label' => 'accounting.settings.fiscal_year_start_month.label']),
                'default_value' => '1',
            ]);
        }
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        $this->connection->delete('app_config', ['setting_key' => AccountingSettings::FISCAL_YEAR_START_MONTH]);
    }
}
