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

namespace Augias\CoreBundle\Enum;

/**
 * Where a {@see \Augias\CoreBundle\Entity\Category} may be used.
 *
 * Purchases and the catalogue used to keep separate, identically-shaped
 * category tables. They are one list now, but the two sides classify opposite
 * halves of the business — what you spend on, and what you sell — so a category
 * declares which dropdowns it belongs in. Without that, choosing a category for
 * a catalogue item would offer "Bank charges", and categorising a supplier
 * invoice would offer "Training".
 *
 * A category may carry both, which is the case the merge exists to serve:
 * "Hardware" that you buy and also resell is one record, not two.
 */
enum CategoryUsage: string
{
    /** Classifies a supplier invoice — an expense. */
    case Purchase = 'purchase';

    /** Groups an item in your own catalogue. */
    case Catalog = 'catalog';

    public function getLabel(): string
    {
        return match ($this) {
            self::Purchase => 'Purchases',
            self::Catalog => 'Catalogue',
        };
    }

    public function translationKey(): string
    {
        return 'category.usage.' . $this->value;
    }

    /**
     * The entity column backing this usage. Kept here so the entity, the
     * repository and the migration all name it in one place.
     */
    public function propertyName(): string
    {
        return match ($this) {
            self::Purchase => 'usedForPurchases',
            self::Catalog => 'usedForCatalog',
        };
    }
}
