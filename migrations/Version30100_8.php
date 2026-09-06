<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30100_8 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bills table (accounts-payable mirror of invoices), manually entered or created from a received electronic invoice';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('bills');
        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('supplier_id', UlidType::NAME);
        $table->addColumn('bill_number', Types::STRING, ['length' => 125, 'notnull' => false]);
        $table->addColumn('status', Types::STRING, ['length' => 20]);
        $table->addColumn('issue_date', Types::DATE_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('due_date', Types::DATE_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('total_amount', BigIntegerType::NAME);
        $table->addColumn('currency_code', Types::STRING, ['length' => 3]);
        $table->addColumn('category_id', UlidType::NAME, ['notnull' => false]);
        $table->addColumn('notes', Types::TEXT, ['notnull' => false]);
        $table->addColumn('electronic_invoice_receipt_id', UlidType::NAME, ['notnull' => false]);
        $table->addColumn('document_path', Types::STRING, ['length' => 512, 'notnull' => false]);
        $table->addColumn('document_mime_type', Types::STRING, ['length' => 100, 'notnull' => false]);
        $table->addColumn('created', Types::DATETIME_IMMUTABLE);
        $table->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $table->addColumn('company_id', UlidType::NAME);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('suppliers', ['supplier_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('bill_categories', ['category_id'], ['id'], ['onDelete' => 'SET NULL']);
        $table->addForeignKeyConstraint('einvoicing_receipt', ['electronic_invoice_receipt_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('bills');
    }
}
