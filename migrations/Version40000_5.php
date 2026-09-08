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
use Doctrine\Migrations\AbstractMigration;

/**
 * Puts every stored percentage discount on one convention: the percentage
 * itself, so 15 means 15%.
 *
 * Two writers disagreed. The API and the MCP tools always stored the plain
 * figure, while the billing form ran its value through a transformer that
 * multiplies by 100 — right for a money discount, which is held in minor units,
 * wrong for a percentage, which it filed as 1500. Calculator then told the two
 * apart by magnitude: anything above 100 was divided by a hundred, anything at
 * or below was used as-is.
 *
 * That guess is gone, so the rows have to say what they mean. Applying the very
 * same rule once, here, leaves every existing discount worth exactly what it
 * was worth on screen yesterday — the only figures it changes are the ones the
 * old code was already dividing.
 *
 * A percentage above 100 is not distinguishable from a scaled one, so a genuine
 * 150% discount — not something the UI offers, and not something an invoice
 * would sensibly carry — would be read down to 1.5%.
 */
final class Version40000_5 extends AbstractMigration
{
    private const array TABLES = ['invoices', 'recurring_invoices', 'quotes'];

    public function getDescription(): string
    {
        return 'Unscale the percentage discounts the billing form stored multiplied by 100';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $table) {
            $this->addSql(
                <<<SQL
                    UPDATE {$table}
                    SET discount_value_percentage = discount_value_percentage / 100
                    WHERE discount_value_percentage > 100
                        AND (discount_type IS NULL OR discount_type = 'percentage')
                    SQL
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Left alone on the way down: which rows were scaled is no longer
        // recorded anywhere, and multiplying them all back would turn the
        // discounts the API and the MCP tools wrote — untouched above — into a
        // hundred times themselves. The old code read a plain percentage at or
        // below 100 correctly, so rolling back leaves every row still working.
    }
}
