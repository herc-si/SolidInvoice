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

use Augias\CoreBundle\Doctrine\Type\BigIntegerType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30100_5 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add einvoicing_receipt, tracking electronic invoices received by a company, imported from a provider that supports reception';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('einvoicing_receipt');
        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('provider', Types::STRING, ['length' => 255]);
        $table->addColumn('external_reference', Types::STRING, ['length' => 255]);
        $table->addColumn('invoice_number', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('seller_name', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('seller_identifier', Types::STRING, ['length' => 64, 'notnull' => false]);
        $table->addColumn('issue_date', Types::DATE_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('total_amount', BigIntegerType::NAME, ['notnull' => false]);
        $table->addColumn('currency_code', Types::STRING, ['length' => 3, 'notnull' => false]);
        $table->addColumn('status_code', Types::STRING, ['length' => 64, 'notnull' => false]);
        $table->addColumn('document_path', Types::STRING, ['length' => 512, 'notnull' => false]);
        $table->addColumn('document_mime_type', Types::STRING, ['length' => 100, 'notnull' => false]);
        $table->addColumn('created', Types::DATETIME_IMMUTABLE);
        $table->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $table->addColumn('company_id', UlidType::NAME);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addUniqueIndex(['company_id', 'provider', 'external_reference'], 'einvoicing_receipt_provider_ref');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('einvoicing_receipt');
    }
}
