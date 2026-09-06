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

use Augias\SettingsBundle\SystemConfig;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Uid\Ulid;
use function json_encode;

final class Version30100_3 extends AbstractMigration
{
    private const SETTING_KEY = SystemConfig::ELECTRONIC_INVOICING_CONFIG_PATH;

    public function getDescription(): string
    {
        return 'Add tables for the pluggable electronic-invoicing providers, and seed the "electronic invoicing enabled" setting for existing companies (new companies get it from TaxBundle\Config\ConfigProvider)';
    }

    public function up(Schema $schema): void
    {
        $providerSettingTable = $schema->createTable('einvoicing_provider_setting');
        $providerSettingTable->addColumn('id', UlidType::NAME);
        $providerSettingTable->addColumn('name', Types::STRING, ['length' => 255]);
        $providerSettingTable->addColumn('provider', Types::STRING, ['length' => 255]);
        $providerSettingTable->addColumn('settings', Types::JSON);
        $providerSettingTable->addColumn('active', Types::BOOLEAN);
        $providerSettingTable->addColumn('created', Types::DATETIME_IMMUTABLE);
        $providerSettingTable->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $providerSettingTable->addColumn('company_id', UlidType::NAME);
        $providerSettingTable->setPrimaryKey(['id']);
        $providerSettingTable->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $providerSettingTable->addUniqueIndex(['name', 'company_id'], 'unique_name_company');

        $submissionTable = $schema->createTable('einvoicing_submission');
        $submissionTable->addColumn('id', UlidType::NAME);
        $submissionTable->addColumn('invoice_id', UlidType::NAME);
        $submissionTable->addColumn('provider', Types::STRING, ['length' => 255]);
        $submissionTable->addColumn('success', Types::BOOLEAN);
        $submissionTable->addColumn('external_reference', Types::STRING, ['length' => 255, 'notnull' => false]);
        $submissionTable->addColumn('message', Types::TEXT, ['notnull' => false]);
        $submissionTable->addColumn('created', Types::DATETIME_IMMUTABLE);
        $submissionTable->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $submissionTable->addColumn('company_id', UlidType::NAME);
        $submissionTable->setPrimaryKey(['id']);
        $submissionTable->addForeignKeyConstraint('invoices', ['invoice_id'], ['id'], ['onDelete' => 'CASCADE']);
        $submissionTable->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $companies = $this->connection->fetchAllAssociative('SELECT id FROM companies');

        foreach ($companies as $company) {
            $companyId = $company['id'];

            $exists = $this->connection->fetchOne(
                'SELECT 1 FROM app_config WHERE company_id = ? AND setting_key = ?',
                [$companyId, self::SETTING_KEY],
            );

            if ($exists !== false) {
                continue;
            }

            $this->connection->insert('app_config', [
                'id' => (new Ulid())->toBinary(),
                'company_id' => $companyId,
                'setting_key' => self::SETTING_KEY,
                'setting_value' => '0',
                'description' => 'tax.electronic_invoicing.description',
                'field_type' => CheckboxType::class,
                'form_options' => json_encode(['label' => 'tax.electronic_invoicing.label']),
                'default_value' => '0',
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('einvoicing_submission');
        $schema->dropTable('einvoicing_provider_setting');
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        $this->connection->delete('app_config', ['setting_key' => self::SETTING_KEY]);
    }
}
