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
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30100_7 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bill_categories table, for expense categorization of accounts-payable bills';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('bill_categories');
        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('name', Types::STRING, ['length' => 125]);
        $table->addColumn('company_id', UlidType::NAME);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name', 'company_id']);
        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('bill_categories');
    }
}
