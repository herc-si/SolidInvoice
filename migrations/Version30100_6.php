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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30100_6 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add suppliers table (accounts-payable mirror of clients), optionally linked to an existing client';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('suppliers');
        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('name', Types::STRING, ['length' => 125]);
        $table->addColumn('email', Types::STRING, ['length' => 125, 'notnull' => false]);
        $table->addColumn('phone', Types::STRING, ['length' => 30, 'notnull' => false]);
        $table->addColumn('tax_identifier', Types::STRING, ['length' => 64, 'notnull' => false]);
        $table->addColumn('street1', Types::STRING, ['length' => 125, 'notnull' => false]);
        $table->addColumn('street2', Types::STRING, ['length' => 125, 'notnull' => false]);
        $table->addColumn('city', Types::STRING, ['length' => 125, 'notnull' => false]);
        $table->addColumn('zip', Types::STRING, ['length' => 20, 'notnull' => false]);
        $table->addColumn('country', Types::STRING, ['length' => 2, 'notnull' => false]);
        $table->addColumn('notes', Types::TEXT, ['notnull' => false]);
        $table->addColumn('archived', Types::BOOLEAN, ['notnull' => false]);
        $table->addColumn('client_id', UlidType::NAME, ['notnull' => false]);
        $table->addColumn('created', Types::DATETIME_IMMUTABLE);
        $table->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $table->addColumn('company_id', UlidType::NAME);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('clients', ['client_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('suppliers');
    }
}
