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

namespace Augias\InvoiceBundle\Listener;

use Augias\ClientBundle\Entity\Credit;
use Augias\ClientBundle\Repository\CreditRepository;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Event\InvoiceEvent;
use Augias\InvoiceBundle\Event\InvoiceEvents;
use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Enum\PaymentStatus;
use Augias\PaymentBundle\Repository\PaymentRepository;
use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function assert;

class InvoiceCancelListener implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceEvents::INVOICE_POST_CANCEL => 'onInvoiceCancelled',
        ];
    }

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    /**
     * @throws MathException
     */
    public function onInvoiceCancelled(InvoiceEvent $event): void
    {
        $invoice = $event->getInvoice();

        assert($invoice instanceof Invoice);

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->registry->getRepository(Payment::class);

        $em = $this->registry->getManager();

        $invoice->setBalance($invoice->getTotal());
        $em->persist($invoice);

        $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);

        if ($totalPaid->isPositive()) {
            $paymentRepository->updatePaymentStatus($invoice->getPayments(), PaymentStatus::Credit);

            /** @var CreditRepository $creditRepository */
            $creditRepository = $this->registry->getRepository(Credit::class);

            $creditRepository->addCredit($invoice->getClient(), $totalPaid);
        }

        $em->flush();
    }
}
