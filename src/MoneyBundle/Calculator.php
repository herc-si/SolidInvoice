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

namespace Augias\MoneyBundle;

use Augias\CoreBundle\Entity\Discount;
use Augias\InvoiceBundle\Entity\BaseInvoice;
use Augias\MoneyBundle\Formatter\MoneyFormatter;
use Augias\QuoteBundle\Entity\Quote;
use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;

/**
 * @see \Augias\MoneyBundle\Tests\CalculatorTest
 */
final class Calculator
{
    /**
     * @throws MathException
     */
    public function calculateDiscount(Quote | BaseInvoice $entity): BigNumber
    {
        $discount = $entity->getDiscount();

        $invoiceTotal = $entity->getBaseTotal()->toBigDecimal()->plus($entity->getTax());

        // Anything that is not an explicit money discount is a percentage —
        // the same reading Discount::getValue() takes, so an unset type cannot
        // fall through to a zero money amount.
        if (Discount::TYPE_MONEY === $discount->getType()) {
            return $discount->getValueMoney();
        }

        // A percentage is stored as the percentage itself — 15 means 15% — so it
        // goes straight to calculatePercentage(). It used to be handed over
        // untouched too, but calculatePercentage() then guessed the scale from
        // the magnitude and divided anything above 100 by a hundred, which was
        // there to absorb the form storing 15% as 1500. The form no longer does
        // that, so nothing has to be guessed.
        return BigDecimal::of((string) $this->calculatePercentage($invoiceTotal, (float) (string) $discount->getValue()));
    }

    /**
     * Takes a plain percentage: 12 means 12%. Also exposed as the `percentage`
     * Twig filter.
     *
     * @throws MathException
     */
    public function calculatePercentage(BigNumber | int | string $amount, float $percentage = 0.0): float
    {
        return MoneyFormatter::toFloat(BigNumber::of($amount)->toBigDecimal()->multipliedBy(BigDecimal::of((string) $percentage)->dividedBy(100, 10, RoundingMode::HalfEven)));
    }
}
