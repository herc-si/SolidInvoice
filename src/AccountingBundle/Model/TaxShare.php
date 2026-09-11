<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\AccountingBundle\Model;

use Augias\TaxBundle\Enum\TaxCategory;
use Brick\Math\BigInteger;

/**
 * One rate's share of what a payment bore in tax.
 *
 * A VAT return is filled in per rate — a base and a tax for each — so a single
 * figure for the whole payment cannot fill it in. The category rides along
 * because two operations at the same rate do not go in the same box: a
 * zero-rated sale and a reverse-charge sale are both 0%, and are declared
 * separately.
 */
final readonly class TaxShare
{
    public function __construct(
        /** As snapshotted on the document: a percentage, "20.0000". */
        public string $rate,
        public TaxCategory $category,
        /** Minor units, the amount the rate applied to. */
        public BigInteger $base,
        /** Minor units. Zero for a zero-rated or reverse-charge operation. */
        public BigInteger $tax,
    ) {
    }

    /**
     * @return array{rate: string, category: string, base: string, tax: string}
     */
    public function toArray(): array
    {
        return [
            'rate' => $this->rate,
            'category' => $this->category->value,
            'base' => (string) $this->base,
            'tax' => (string) $this->tax,
        ];
    }
}
