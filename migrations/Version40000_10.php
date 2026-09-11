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
use Augias\AccountingBundle\Form\Type\VatPeriodicityChoiceType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Ulid;
use function json_encode;

/**
 * Seeds the VAT periodicity for companies that already exist.
 *
 * Empty, which means VAT is declared on the same rhythm as the books — what the
 * module did before there was a second cycle, and what most companies want.
 */
final class Version40000_10 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed the VAT periodicity setting for existing companies';
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
                [$company['id'], AccountingSettings::VAT_PERIODICITY],
            );

            if ($exists !== false) {
                continue;
            }

            $this->connection->insert('app_config', [
                'id' => new Ulid()->toBinary(),
                'company_id' => $company['id'],
                'setting_key' => AccountingSettings::VAT_PERIODICITY,
                'setting_value' => null,
                'description' => 'accounting.settings.vat_periodicity.description',
                'field_type' => VatPeriodicityChoiceType::class,
                'form_options' => json_encode(['label' => 'accounting.settings.vat_periodicity.label']),
                'default_value' => null,
            ]);
        }
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        $this->connection->delete('app_config', ['setting_key' => AccountingSettings::VAT_PERIODICITY]);
    }
}
