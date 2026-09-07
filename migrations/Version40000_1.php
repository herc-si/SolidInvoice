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

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\Declaration;
use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Entity\ThresholdAlert;
use Augias\CoreBundle\Doctrine\Type\BigIntegerType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version40000_1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the accounting tables: the two statutory ledgers, the periods they are closed at, the turnover declarations derived from them, and the threshold alerts';
    }

    public function up(Schema $schema): void
    {
        $periods = $schema->createTable(AccountingPeriod::TABLE_NAME);
        $periods->addColumn('id', UlidType::NAME);
        $periods->addColumn('period_type', Types::STRING, ['length' => 10]);
        $periods->addColumn('period_year', Types::SMALLINT);
        $periods->addColumn('period_ordinal', Types::SMALLINT);
        $periods->addColumn('start_date', Types::DATE_IMMUTABLE);
        $periods->addColumn('end_date', Types::DATE_IMMUTABLE);
        $periods->addColumn('status', Types::STRING, ['length' => 10]);
        $periods->addColumn('closed_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $periods->addColumn('closed_by_id', UlidType::NAME, ['notnull' => false]);
        $periods->addColumn('closing_hash', Types::STRING, ['length' => 64, 'notnull' => false]);
        $periods->addColumn('entry_count', Types::INTEGER, ['default' => 0]);
        $periods->addColumn('totals', Types::JSON, ['notnull' => false]);
        $periods->addColumn('created', Types::DATETIME_IMMUTABLE);
        $periods->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $periods->addColumn('company_id', UlidType::NAME);
        $periods->setPrimaryKey(['id']);
        $periods->addUniqueIndex(
            ['company_id', 'period_type', 'period_year', 'period_ordinal'],
            'unique_period_company',
        );
        $periods->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        // The user who closed a period may well be gone by the time anyone
        // audits it; the closure itself has to survive them.
        $periods->addForeignKeyConstraint('users', ['closed_by_id'], ['id'], ['onDelete' => 'SET NULL']);

        $entries = $schema->createTable(LedgerEntry::TABLE_NAME);
        $entries->addColumn('id', UlidType::NAME);
        $entries->addColumn('book', Types::STRING, ['length' => 10]);
        $entries->addColumn('sequence_number', Types::INTEGER, ['notnull' => false]);
        $entries->addColumn('entry_date', Types::DATE_IMMUTABLE);
        $entries->addColumn('label', Types::STRING, ['length' => 255]);
        $entries->addColumn('counterparty_name', Types::STRING, ['length' => 255]);
        $entries->addColumn('counterparty_id', UlidType::NAME, ['notnull' => false]);
        $entries->addColumn('document_reference', Types::STRING, ['length' => 125, 'notnull' => false]);
        $entries->addColumn('amount', BigIntegerType::NAME);
        $entries->addColumn('currency_code', Types::STRING, ['length' => 3]);
        $entries->addColumn('activity_nature', Types::STRING, ['length' => 20, 'notnull' => false]);
        $entries->addColumn('settlement_method', Types::STRING, ['length' => 20, 'notnull' => false]);
        $entries->addColumn('source', Types::STRING, ['length' => 20]);
        $entries->addColumn('source_id', UlidType::NAME, ['notnull' => false]);
        $entries->addColumn('period_id', UlidType::NAME, ['notnull' => false]);
        $entries->addColumn('late_entry', Types::BOOLEAN, ['default' => false]);
        $entries->addColumn('entry_hash', Types::STRING, ['length' => 64, 'notnull' => false]);
        $entries->addColumn('previous_hash', Types::STRING, ['length' => 64, 'notnull' => false]);
        $entries->addColumn('locked_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $entries->addColumn('reverses_id', UlidType::NAME, ['notnull' => false]);
        $entries->addColumn('notes', Types::TEXT, ['notnull' => false]);
        $entries->addColumn('created', Types::DATETIME_IMMUTABLE);
        $entries->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $entries->addColumn('company_id', UlidType::NAME);
        $entries->setPrimaryKey(['id']);
        $entries->addIndex(['company_id', 'book', 'entry_date'], 'idx_ledger_book_date');
        // What keeps the automatic feeders idempotent. Manual entries leave
        // source_id null, and SQL keeps NULLs out of a unique index, so they
        // never collide with one another.
        $entries->addUniqueIndex(['company_id', 'book', 'source', 'source_id'], 'unique_ledger_source');
        $entries->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        // A deleted client must not take the book with it — the entry keeps the
        // denormalised counterparty_name for exactly this case.
        $entries->addForeignKeyConstraint('clients', ['counterparty_id'], ['id'], ['onDelete' => 'SET NULL']);
        $entries->addForeignKeyConstraint(
            AccountingPeriod::TABLE_NAME,
            ['period_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
        );
        $entries->addForeignKeyConstraint(
            LedgerEntry::TABLE_NAME,
            ['reverses_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
        );

        $declarations = $schema->createTable(Declaration::TABLE_NAME);
        $declarations->addColumn('id', UlidType::NAME);
        $declarations->addColumn('period_id', UlidType::NAME);
        $declarations->addColumn('regime_code', Types::STRING, ['length' => 50]);
        $declarations->addColumn('rate_version', Types::STRING, ['length' => 20, 'notnull' => false]);
        $declarations->addColumn('status', Types::STRING, ['length' => 20]);
        $declarations->addColumn('currency_code', Types::STRING, ['length' => 3]);
        $declarations->addColumn('total_turnover', BigIntegerType::NAME);
        $declarations->addColumn('total_contributions', BigIntegerType::NAME);
        $declarations->addColumn('total_due', BigIntegerType::NAME);
        $declarations->addColumn('lines', Types::JSON);
        $declarations->addColumn('submitted_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $declarations->addColumn('reference', Types::STRING, ['length' => 125, 'notnull' => false]);
        $declarations->addColumn('notes', Types::TEXT, ['notnull' => false]);
        $declarations->addColumn('created', Types::DATETIME_IMMUTABLE);
        $declarations->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $declarations->addColumn('company_id', UlidType::NAME);
        $declarations->setPrimaryKey(['id']);
        $declarations->addUniqueIndex(['period_id'], 'unique_declaration_period');
        $declarations->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $declarations->addForeignKeyConstraint(
            AccountingPeriod::TABLE_NAME,
            ['period_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
        );

        $alerts = $schema->createTable(ThresholdAlert::TABLE_NAME);
        $alerts->addColumn('id', UlidType::NAME);
        $alerts->addColumn('threshold_key', Types::STRING, ['length' => 100]);
        $alerts->addColumn('period_year', Types::SMALLINT);
        $alerts->addColumn('step', Types::SMALLINT);
        $alerts->addColumn('amount', BigIntegerType::NAME);
        $alerts->addColumn('threshold_amount', BigIntegerType::NAME);
        $alerts->addColumn('currency_code', Types::STRING, ['length' => 3]);
        $alerts->addColumn('triggered_at', Types::DATETIME_IMMUTABLE);
        $alerts->addColumn('notified_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $alerts->addColumn('created', Types::DATETIME_IMMUTABLE);
        $alerts->addColumn('updated', Types::DATETIME_IMMUTABLE);
        $alerts->addColumn('company_id', UlidType::NAME);
        $alerts->setPrimaryKey(['id']);
        // One milestone raised once a year — this is the whole reason the table
        // exists, rather than recomputing alerts on the fly every morning.
        $alerts->addUniqueIndex(
            ['company_id', 'threshold_key', 'period_year', 'step'],
            'unique_threshold_alert',
        );
        $alerts->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(ThresholdAlert::TABLE_NAME);
        $schema->dropTable(Declaration::TABLE_NAME);
        $schema->dropTable(LedgerEntry::TABLE_NAME);
        $schema->dropTable(AccountingPeriod::TABLE_NAME);
    }
}
