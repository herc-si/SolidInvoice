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

namespace Augias\InvoiceBundle\Twig\Extension;

use Augias\ClientBundle\Entity\Contact;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Enum\PaymentStatus;
use Twig\Attribute\AsTwigFunction;
use function array_filter;
use function array_values;

/**
 * @see \Augias\InvoiceBundle\Tests\Twig\Extension\InvoiceTemplateExtensionTest
 */
final class InvoiceTemplateExtension
{
    #[AsTwigFunction(name: 'invoice_has_outstanding_balance')]
    public function hasOutstandingBalance(Invoice $invoice): bool
    {
        if (! $invoice->getBalance()->isPositive()) {
            return false;
        }

        return $this->capturedPayments($invoice) !== [];
    }

    /**
     * @return list<Payment>
     */
    #[AsTwigFunction(name: 'invoice_captured_payments')]
    public function capturedPayments(Invoice $invoice): array
    {
        return array_values(array_filter(
            $invoice->getPayments()->toArray(),
            static fn (Payment $payment): bool => $payment->getStatus() === PaymentStatus::Captured,
        ));
    }

    #[AsTwigFunction(name: 'invoice_primary_contact')]
    public function primaryContact(Invoice $invoice): ?Contact
    {
        $first = $invoice->getUsers()->first();

        return $first instanceof Contact ? $first : null;
    }
}
