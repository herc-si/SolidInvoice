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

final class Version30100_12 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add products and product_categories tables, a re-usable catalogue for invoice and quote lines';
    }

    public function up(Schema $schema): void
    {
        $categories = $schema->createTable('product_categories');
        $categories->addColumn('id', UlidType::NAME);
        $categories->addColumn('name', Types::STRING, ['length' => 125]);
        $categories->addColumn('company_id', UlidType::NAME);
        $categories->setPrimaryKey(['id']);
        $categories->addUniqueIndex(['name', 'company_id']);
        $categories->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);

        $products = $schema->createTable('products');
        $products->addColumn('id', UlidType::NAME);
        $products->addColumn('name', Types::STRING, ['length' => 125]);
        $products->addColumn('description', Types::TEXT, ['notnull' => false]);
        $products->addColumn('reference', Types::STRING, ['length' => 64, 'notnull' => false]);
        $products->addColumn('type', Types::STRING, ['length' => 16]);
        $products->addColumn('unit', Types::STRING, ['length' => 16]);
        $products->addColumn('sale_price', BigIntegerType::NAME);
        $products->addColumn('purchase_price', BigIntegerType::NAME, ['notnull' => false]);
        $products->addColumn('active', Types::BOOLEAN, ['default' => true]);
        $products->addColumn('created', Types::DATETIME_IMMUTABLE);
        $products->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $products->addColumn('tax_id', UlidType::NAME, ['notnull' => false]);
        $products->addColumn('category_id', UlidType::NAME, ['notnull' => false]);
        $products->addColumn('company_id', UlidType::NAME);
        $products->setPrimaryKey(['id']);
        // Only meaningful when a reference is set; SQL leaves NULLs out of a
        // unique index, so entries without one never collide.
        $products->addUniqueIndex(['reference', 'company_id']);
        $products->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $products->addForeignKeyConstraint('tax_rates', ['tax_id'], ['id'], ['onDelete' => 'SET NULL']);
        $products->addForeignKeyConstraint('product_categories', ['category_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('products');
        $schema->dropTable('product_categories');
    }
}
