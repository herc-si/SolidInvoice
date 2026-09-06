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

namespace Augias\TaxBundle\Validator\Constraints;

use Augias\InvoiceBundle\Entity\Line;
use Augias\TaxBundle\Entity\LineTax;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ExactlyOneLineValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ExactlyOneLine) {
            throw new UnexpectedTypeException($constraint, ExactlyOneLine::class);
        }

        if ($value === null) {
            return;
        }

        if (! $value instanceof LineTax) {
            throw new UnexpectedValueException($value, LineTax::class);
        }

        $invoiceLine = $value->getInvoiceLine();
        $quoteLine = $value->getQuoteLine();

        $hasInvoice = $invoiceLine instanceof Line;
        $hasQuote = $quoteLine instanceof \Augias\QuoteBundle\Entity\Line;

        if ($hasInvoice === $hasQuote) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
