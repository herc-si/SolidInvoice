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

namespace Augias\TaxBundle\Calculator;

use Augias\InvoiceBundle\Entity\BaseInvoice;
use Augias\QuoteBundle\Entity\Quote;
use Augias\TaxBundle\Calculator\Result\CalculationResult;

interface TaxCalculatorInterface
{
    public function calculate(BaseInvoice | Quote $document, ?CalculationOptions $options = null): CalculationResult;
}
