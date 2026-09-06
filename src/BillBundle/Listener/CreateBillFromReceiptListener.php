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

namespace SolidInvoice\BillBundle\Listener;

use Psr\Log\LoggerInterface;
use SolidInvoice\BillBundle\Entity\Bill;
use SolidInvoice\BillBundle\Manager\BillManager;
use SolidInvoice\BillBundle\Repository\BillRepository;
use SolidInvoice\ElectronicInvoicingBundle\Event\ElectronicInvoiceReceiptImportedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Throwable;

/**
 * Files every electronically received invoice as a purchase invoice as soon as
 * it lands, creating the supplier when none matches — see
 * {@see BillManager::createFromReceipt()}.
 *
 * @see \SolidInvoice\BillBundle\Tests\Listener\CreateBillFromReceiptListenerTest
 */
#[AsEventListener(event: ElectronicInvoiceReceiptImportedEvent::class)]
final readonly class CreateBillFromReceiptListener
{
    public function __construct(
        private BillRepository $billRepository,
        private BillManager $billManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ElectronicInvoiceReceiptImportedEvent $event): void
    {
        $receipt = $event->receipt;

        if ($this->billRepository->findOneBy(['electronicInvoiceReceipt' => $receipt]) instanceof Bill) {
            return;
        }

        try {
            $this->billManager->createFromReceipt($receipt);
        } catch (Throwable $e) {
            // One unconvertible receipt must not abort the whole import batch.
            // It simply stays unfiled, which is what the pending-receipts panel
            // on the purchase-invoices page surfaces.
            $this->logger->error('Failed to create a bill from an incoming electronic invoice', [
                'receipt_id' => (string) $receipt->getId(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
