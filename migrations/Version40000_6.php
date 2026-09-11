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
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Uid\Ulid;
use function json_encode;

/**
 * Seeds the lock date for companies that already exist.
 *
 * Same reason as {@see Version40000_2}: new companies get their settings from
 * {@see \Augias\AccountingBundle\Config\AccountingConfigProvider}, and
 * {@see \Augias\SettingsBundle\Repository\SettingsRepository::store()} only ever
 * updates a row, so a setting with no row is a setting the user cannot save.
 *
 * Seeded empty, which means nothing is locked by hand. That is the only safe
 * starting point: guessing a date here would shut books the user never asked to
 * shut. What is already sealed stays beyond reach regardless — the seal is what
 * {@see \Augias\AccountingBundle\Service\LedgerLockDate} raises this to.
 */
final class Version40000_6 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed the accounting lock date setting for existing companies';
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
        $companies = $this->connection->fetchAllAssociative('SELECT id FROM companies');

        foreach ($companies as $company) {
            $exists = $this->connection->fetchOne(
                'SELECT 1 FROM app_config WHERE company_id = ? AND setting_key = ?',
                [$company['id'], AccountingSettings::LOCK_DATE],
            );

            if ($exists !== false) {
                continue;
            }

            $this->connection->insert('app_config', [
                'id' => new Ulid()->toBinary(),
                'company_id' => $company['id'],
                'setting_key' => AccountingSettings::LOCK_DATE,
                'setting_value' => null,
                'description' => 'accounting.settings.lock_date.description',
                'field_type' => DateType::class,
                // Spelled out in full, unlike the label-only options of
                // Version40000_2: settings round-trip as plain strings, so a
                // date field that is not told so cannot read back what it wrote.
                'form_options' => json_encode([
                    'label' => 'accounting.settings.lock_date.label',
                    'input' => 'string',
                    'widget' => 'single_text',
                ]),
                'default_value' => null,
            ]);
        }
    }

    /**
     * @throws Exception
     */
    public function postDown(Schema $schema): void
    {
        $this->connection->delete('app_config', ['setting_key' => AccountingSettings::LOCK_DATE]);
    }
}
