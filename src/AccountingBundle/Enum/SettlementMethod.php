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

namespace Augias\AccountingBundle\Enum;

use Augias\BillBundle\Enum\BillPaymentMethod;

/**
 * The "mode de règlement" column both statutory books must carry. Deliberately
 * its own set rather than a reuse of {@see BillPaymentMethod}: that one only
 * ever describes money going *out* to a supplier and has no case for an online
 * gateway capture, which is how most incoming payments are settled here.
 *
 * {@see self::fromBillPaymentMethod()} maps the purchase side across.
 */
enum SettlementMethod: string
{
    case BankTransfer = 'bank_transfer';

    case Check = 'check';

    case CreditCard = 'credit_card';

    case DirectDebit = 'direct_debit';

    case Cash = 'cash';

    /** Captured through a payment gateway — the usual case for an invoice paid online. */
    case Online = 'online';

    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank Transfer',
            self::Check => 'Check',
            self::CreditCard => 'Credit Card',
            self::DirectDebit => 'Direct Debit',
            self::Cash => 'Cash',
            self::Online => 'Online payment',
            self::Other => 'Other',
        };
    }

    public function translationKey(): string
    {
        return 'accounting.settlement_method.' . $this->value;
    }

    public static function fromBillPaymentMethod(BillPaymentMethod $method): self
    {
        return match ($method) {
            BillPaymentMethod::BankTransfer => self::BankTransfer,
            BillPaymentMethod::Check => self::Check,
            BillPaymentMethod::CreditCard => self::CreditCard,
            BillPaymentMethod::Cash => self::Cash,
            BillPaymentMethod::Other => self::Other,
        };
    }

    /**
     * Best-effort reading of a Payum gateway name. Gateways are configured by
     * the user and named freely, so anything unrecognised settles on
     * {@see self::Online} — it is at least true of every gateway capture, which
     * is more useful in the book than "other".
     */
    public static function fromGatewayName(?string $gateway): self
    {
        return match ($gateway) {
            null, '' => self::Other,
            'cash' => self::Cash,
            'bank_transfer', 'banktransfer' => self::BankTransfer,
            'credit' => self::Other,
            default => self::Online,
        };
    }

    /**
     * @return array<string, string> translation key => value, for form choices
     */
    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $case) {
            $choices[$case->translationKey()] = $case->value;
        }

        return $choices;
    }
}
