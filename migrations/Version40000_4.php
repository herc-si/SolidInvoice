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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * Second half of the category merge — see {@see Version40000_3} for why it is
 * split in two.
 *
 * By the time this runs, `bills.category_id` and `products.category_id` already
 * hold ids from the merged table, so the foreign keys can be pointed at it and
 * the two old tables have nothing left in them worth keeping.
 */
final class Version40000_4 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Point the bill and product category foreign keys at the merged categories table, and drop the two old tables';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('bills')
            ->addForeignKeyConstraint(Category::TABLE_NAME, ['category_id'], ['id'], ['onDelete' => 'SET NULL']);
        $schema->getTable('products')
            ->addForeignKeyConstraint(Category::TABLE_NAME, ['category_id'], ['id'], ['onDelete' => 'SET NULL']);

        $schema->dropTable('bill_categories');
        $schema->dropTable('product_categories');
    }

    public function down(Schema $schema): void
    {
        // Recreated empty: splitting the merged list back in two would have to
        // guess which side a category belonged to when it was flagged for both,
        // and there is no answer to that which does not invent data. Rolling
        // back this far means re-entering the categories by hand.
        $bill = $schema->createTable('bill_categories');
        $bill->addColumn('id', UlidType::NAME);
        $bill->addColumn('name', Types::STRING, ['length' => 125]);
        $bill->addColumn('company_id', UlidType::NAME);
        $bill->setPrimaryKey(['id']);
        $bill->addUniqueIndex(['name', 'company_id']);
        $bill->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);

        $product = $schema->createTable('product_categories');
        $product->addColumn('id', UlidType::NAME);
        $product->addColumn('name', Types::STRING, ['length' => 125]);
        $product->addColumn('company_id', UlidType::NAME);
        $product->setPrimaryKey(['id']);
        $product->addUniqueIndex(['name', 'company_id']);
        $product->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);

        foreach (['bills', 'products'] as $table) {
            $subject = $schema->getTable($table);

            foreach ($subject->getForeignKeys() as $name => $foreignKey) {
                if ($foreignKey->getForeignTableName() === Category::TABLE_NAME) {
                    $subject->removeForeignKey((string) $name);
                }
            }
        }

        $schema->getTable('bills')
            ->addForeignKeyConstraint('bill_categories', ['category_id'], ['id'], ['onDelete' => 'SET NULL']);
        $schema->getTable('products')
            ->addForeignKeyConstraint('product_categories', ['category_id'], ['id'], ['onDelete' => 'SET NULL']);
    }
}
