<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\BillBundle\Manager;

use Brick\Math\BigNumber;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\BillBundle\Entity\Bill;
use SolidInvoice\BillBundle\Entity\BillPayment;
use SolidInvoice\BillBundle\Enum\BillPaymentMethod;
use SolidInvoice\BillBundle\Model\Graph;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Records money paid against a {@see Bill} and reacts to it — the
 * accounts-payable equivalent of how a captured {@see \SolidInvoice\PaymentBundle\Entity\Payment}
 * eventually drives an Invoice to {@see \SolidInvoice\InvoiceBundle\Enum\InvoiceStatus::Paid}.
 * There's no Payum capture callback to hook here, so this manager itself
 * decides when the bill is fully paid and applies the `pay` transition,
 * right after persisting the payment.
 *
 * @see \SolidInvoice\BillBundle\Tests\Manager\BillPaymentManagerTest
 */
final readonly class BillPaymentManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkflowInterface $billStateMachine,
    ) {
    }

    public function recordPayment(
        Bill $bill,
        BigNumber $amount,
        DateTimeImmutable $paidDate,
        BillPaymentMethod $method,
        ?string $reference = null,
        ?string $notes = null,
    ): BillPayment {
        $payment = new BillPayment();
        $payment->setCompany($bill->getCompany())
            ->setBill($bill)
            ->setAmount($amount)
            ->setCurrencyCode($bill->getCurrencyCode())
            ->setPaidDate($paidDate)
            ->setMethod($method)
            ->setReference($reference)
            ->setNotes($notes);

        $bill->addPayment($payment);
        $this->entityManager->persist($payment);

        if ($bill->getBalance()->isZero() && $this->billStateMachine->can($bill, Graph::TRANSITION_PAY)) {
            $this->billStateMachine->apply($bill, Graph::TRANSITION_PAY);
        }

        $this->entityManager->flush();

        return $payment;
    }
}
