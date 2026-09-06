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

/**
 * Distinguishes a registered business from a private individual, so
 * electronic-invoicing's mandatory SIRET requirement (French B2B e-invoicing
 * rules only apply between VAT-registered businesses) isn't wrongly demanded
 * from an individual client.
 */
final class Version30100_11 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_company to clients, so individuals are exempt from the SIRET requirement';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('clients')->addColumn('is_company', Types::BOOLEAN, ['default' => true]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('clients')->dropColumn('is_company');
    }
}
