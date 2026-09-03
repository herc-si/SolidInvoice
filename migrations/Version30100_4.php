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

final class Version30100_4 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add einvoicing_submission.status_code, populated by a provider polling command since not every electronic-invoicing platform exposes webhooks';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('einvoicing_submission')
            ->addColumn('status_code', Types::STRING, ['length' => 64, 'notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('einvoicing_submission')->dropColumn('status_code');
    }
}
