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

/**
 * Reconstructed from the resulting schema after the original file was lost:
 * it carries the structural change only, with no data migration, since the
 * suppliers table is dropped outright rather than folded into clients.
 */
final class Version30100_10 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fold suppliers into clients: a single party record flagged as client, supplier, or both';
    }

    public function up(Schema $schema): void
    {
        $clients = $schema->getTable('clients');
        $clients->addColumn('is_client', Types::BOOLEAN, ['default' => true]);
        $clients->addColumn('is_supplier', Types::BOOLEAN, ['default' => false]);

        // A bill's supplier is now an ordinary client row carrying is_supplier.
        $bills = $schema->getTable('bills');

        foreach ($bills->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getForeignTableName() === 'suppliers') {
                $bills->removeForeignKey($foreignKey->getName());
            }
        }

        $bills->addForeignKeyConstraint('clients', ['supplier_id'], ['id'], ['onDelete' => 'CASCADE']);

        $schema->dropTable('suppliers');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Dropping the suppliers table cannot be undone without its data.');
    }
}
