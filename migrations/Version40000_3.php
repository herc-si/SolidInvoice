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

use Augias\CoreBundle\Entity\Category;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function sprintf;

/**
 * First half of merging `bill_categories` and `product_categories` into one
 * `categories` table: create the new table, unhook the old foreign keys, and
 * move the data across. {@see Version40000_4} then points the foreign keys at
 * the new table and drops the old ones.
 *
 * It takes two migrations because of an ordering constraint that cannot be
 * satisfied inside one. Doctrine applies a migration's schema changes before
 * its `postUp()`, and `bills.category_id` cannot be repointed at a
 * `categories` row while a foreign key still ties it to `bill_categories` —
 * nor can the new foreign key be added before the data it would validate has
 * moved. So: drop the constraints and move the data here, re-establish them
 * next.
 *
 * The two tables were byte-for-byte identical in shape and sat side by side in
 * the settings menu, both called "categories". What they classify is not
 * identical — expenses on one side, your own catalogue on the other — so the
 * merged rows carry a flag per side, and each dropdown filters on its own.
 *
 * Nothing is dropped: a name used on both sides by the same company becomes a
 * single row flagged for both, which is exactly the case the merge is for.
 */
final class Version40000_3 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the merged categories table, release the old category foreign keys and move bill/product categories across';
    }

    public function up(Schema $schema): void
    {
        $categories = $schema->createTable(Category::TABLE_NAME);
        $categories->addColumn('id', UlidType::NAME);
        $categories->addColumn('name', Types::STRING, ['length' => 125]);
        $categories->addColumn('used_for_purchases', Types::BOOLEAN, ['default' => false]);
        $categories->addColumn('used_for_catalog', Types::BOOLEAN, ['default' => false]);
        $categories->addColumn('company_id', UlidType::NAME);
        $categories->setPrimaryKey(['id']);
        $categories->addUniqueIndex(['name', 'company_id']);
        $categories->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);

        // The columns stay and keep their values; only the constraints go, so
        // postUp() can rewrite them to point at the new table.
        self::dropForeignKeyTo($schema->getTable('bills'), 'bill_categories');
        self::dropForeignKeyTo($schema->getTable('products'), 'product_categories');
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        // company + name => the new id, so a category present on both sides is
        // created once and comes out flagged for both.
        $ids = [];

        foreach ([
            'bill_categories' => ['used_for_purchases', 'bills'],
            'product_categories' => ['used_for_catalog', 'products'],
        ] as $table => [$flag, $referencingTable]) {
            $rows = $this->connection->fetchAllAssociative(
                sprintf('SELECT id, name, company_id FROM %s', $table),
            );

            foreach ($rows as $row) {
                $key = $row['company_id'] . "\0" . $row['name'];

                if (! isset($ids[$key])) {
                    $ids[$key] = new Ulid()->toBinary();

                    $this->connection->insert(Category::TABLE_NAME, [
                        'id' => $ids[$key],
                        'name' => $row['name'],
                        'company_id' => $row['company_id'],
                        'used_for_purchases' => 0,
                        'used_for_catalog' => 0,
                    ]);
                }

                $this->connection->update(Category::TABLE_NAME, [$flag => 1], ['id' => $ids[$key]]);
                $this->connection->update($referencingTable, ['category_id' => $ids[$key]], ['category_id' => $row['id']]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(Category::TABLE_NAME);

        $schema->getTable('bills')
            ->addForeignKeyConstraint('bill_categories', ['category_id'], ['id'], ['onDelete' => 'SET NULL']);
        $schema->getTable('products')
            ->addForeignKeyConstraint('product_categories', ['category_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    /**
     * Foreign keys get generated names, so the one to remove is found by what
     * it points at rather than by a name this file would have to guess.
     */
    private static function dropForeignKeyTo(Table $table, string $foreignTable): void
    {
        foreach ($table->getForeignKeys() as $name => $foreignKey) {
            if ($foreignKey->getForeignTableName() === $foreignTable) {
                $table->removeForeignKey((string) $name);
            }
        }
    }
}
