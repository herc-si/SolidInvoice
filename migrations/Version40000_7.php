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

use Augias\AccountingBundle\Entity\LedgerEntry;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Records the tax contained in a ledger entry, split by rate.
 *
 * The books kept a single amount, which is all a micro-entreprise in franchise
 * en base ever needs. A company that charges VAT declares, per rate, a base and
 * a tax collected — and neither is recoverable from a lump sum after the fact,
 * least of all once the period is sealed and the entry immutable.
 *
 * All three are nullable and stay null on every entry written so far. Null is
 * not zero here: it means tax did not apply, where zero would mean a taxable
 * operation that bore none, which is a different thing to declare.
 */
final class Version40000_7 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the net, tax and per-rate tax breakdown to ledger entries';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(LedgerEntry::TABLE_NAME);

        $table->addColumn('net_amount', Types::BIGINT, ['notnull' => false]);
        $table->addColumn('tax_amount', Types::BIGINT, ['notnull' => false]);
        $table->addColumn('tax_breakdown', Types::JSON, ['notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(LedgerEntry::TABLE_NAME);

        $table->dropColumn('net_amount');
        $table->dropColumn('tax_amount');
        $table->dropColumn('tax_breakdown');
    }
}
