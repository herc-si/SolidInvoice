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

namespace Augias\BillBundle\Enum;

/**
 * How a {@see \Augias\BillBundle\Entity\BillPayment} was actually paid —
 * a small, purpose-built set for a manually-recorded outgoing payment, unlike
 * {@see \Augias\PaymentBundle\Enum\PaymentStatus} (an 11-case set of
 * Payum *gateway capture states*, not applicable here since no gateway is
 * involved in paying a supplier).
 */
enum BillPaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Check = 'check';
    case CreditCard = 'credit_card';
    case Cash = 'cash';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank Transfer',
            self::Check => 'Check',
            self::CreditCard => 'Credit Card',
            self::Cash => 'Cash',
            self::Other => 'Other',
        };
    }
}
