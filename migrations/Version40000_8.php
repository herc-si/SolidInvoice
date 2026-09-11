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

use Augias\BillBundle\Entity\Bill;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Records the VAT a supplier charged on a bill.
 *
 * A bill carried a total and nothing else, which is all a company that deducts
 * nothing ever needs. One that reclaims VAT has to declare what it paid, and
 * that figure is printed on the supplier's document — so it is asked for and
 * stored, not derived from a rate.
 *
 * Nullable, and null on every bill recorded so far. Null is not zero: it means
 * tax does not apply, where zero would say the supplier charged none.
 */
final class Version40000_8 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the supplier tax amount to bills';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable(Bill::TABLE_NAME)
            ->addColumn('tax_amount', Types::BIGINT, ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable(Bill::TABLE_NAME)
            ->dropColumn('tax_amount');
    }
}
