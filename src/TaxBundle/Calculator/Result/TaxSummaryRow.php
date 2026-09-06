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

namespace Augias\TaxBundle\Calculator\Result;

use Augias\TaxBundle\Enum\TaxCategory;
use Augias\TaxBundle\Enum\TaxDirection;
use Augias\TaxBundle\Enum\TaxType;
use Brick\Math\BigDecimal;

/**
 * Per-tax breakdown line, suitable for rendering on an invoice/quote summary.
 *
 * {@see $direction} and {@see $note} are only populated for invoice-level rows
 * (see {@see InvoiceLevelBreakdown}); line-level rows leave them as defaults.
 */
final readonly class TaxSummaryRow
{
    public function __construct(
        public string $name,
        public string $rate,
        public TaxCategory $category,
        public TaxType $type,
        public bool $compound,
        public BigDecimal $amount,
        public int $sequence = 0,
        public TaxDirection $direction = TaxDirection::Additive,
        public ?string $note = null,
    ) {
    }
}
