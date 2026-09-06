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

final class Version30100_9 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bill_payments table, recording money paid out against a bill';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('bill_payments');
        $table->addColumn('id', UlidType::NAME);
        $table->addColumn('bill_id', UlidType::NAME);
        $table->addColumn('amount', BigIntegerType::NAME);
        $table->addColumn('currency_code', Types::STRING, ['length' => 3]);
        $table->addColumn('paid_date', Types::DATE_IMMUTABLE);
        $table->addColumn('method', Types::STRING, ['length' => 20]);
        $table->addColumn('reference', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('notes', Types::TEXT, ['notnull' => false]);
        $table->addColumn('created', Types::DATETIME_IMMUTABLE);
        $table->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $table->addColumn('company_id', UlidType::NAME);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $table->addForeignKeyConstraint('bills', ['bill_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('bill_payments');
    }
}
