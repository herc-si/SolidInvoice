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

use Augias\AccountingBundle\Entity\Declaration;
use Augias\AccountingBundle\Enum\DeclarationKind;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lets a period owe more than one return.
 *
 * A declaration was unique per period, which said a quarter could only ever be
 * declared once. A micro-entrepreneur past the franchise threshold declares
 * turnover to URSSAF and VAT to the tax office for that same quarter: two
 * forms, two references, two filings.
 *
 * Everything already stored is a turnover declaration, so that is what the
 * column defaults to and what existing rows become. The uniqueness that
 * mattered is kept, one rank lower: one declaration per period per kind.
 */
final class Version40000_9 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow one declaration per kind per period rather than one per period';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(Declaration::TABLE_NAME);

        $table->addColumn('kind', Types::STRING, [
            'length' => 30,
            'notnull' => true,
            'default' => DeclarationKind::SocialContributions->value,
        ]);

        $table->dropIndex('unique_declaration_period');
        $table->addUniqueIndex(['period_id', 'kind'], 'unique_declaration_period_kind');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(Declaration::TABLE_NAME);

        $table->dropIndex('unique_declaration_period_kind');
        $table->addUniqueIndex(['period_id'], 'unique_declaration_period');
        $table->dropColumn('kind');
    }
}
