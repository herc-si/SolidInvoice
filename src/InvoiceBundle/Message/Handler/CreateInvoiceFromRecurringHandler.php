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

namespace Augias\InvoiceBundle\Message\Handler;

use Augias\CoreBundle\Company\CompanySelector;
use Augias\InvoiceBundle\Exception\InvalidTransitionException;
use Augias\InvoiceBundle\Manager\InvoiceManager;
use Augias\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use Augias\InvoiceBundle\Model\Graph;
use Augias\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Brick\Math\Exception\MathException;
use JsonException;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @see \Augias\InvoiceBundle\Tests\Message\Handler\CreateInvoiceFromRecurringHandlerTest
 */
#[AsMessageHandler(fromTransport: 'sync')]
final readonly class CreateInvoiceFromRecurringHandler
{
    public function __construct(
        private InvoiceManager $invoiceManager,
        private WorkflowInterface $invoiceStateMachine,
        private CompanySelector $companySelector,
        private LoggerInterface $logger,
        private ClockInterface $clock,
        private RecurringInvoiceRepository $recurringInvoiceRepository,
    ) {
    }

    public function __invoke(CreateInvoiceFromRecurring $message): void
    {
        $invoice = $this->recurringInvoiceRepository->find($message->getRecurringInvoiceId());
        if (null === $invoice) {
            $this->logger->error('Recurring invoice not found', ['recurring_invoice_id' => $message->getRecurringInvoiceId()]);

            return;
        }

        $this->companySelector->switchCompany($invoice->getCompany()->getId());

        try {
            if ($invoice->hasInvoiceForDay($this->clock->now())) {
                return;
            }

            $newInvoice = $this->invoiceManager->createFromRecurring($invoice);
            $this->invoiceManager->create($newInvoice);
            $this->invoiceStateMachine->apply($newInvoice, Graph::TRANSITION_ACCEPT);
        } catch (MathException | InvalidTransitionException | JsonException $e) {
            $this->logger->error('An error occurred while creating invoice from recurring', ['exception' => $e]);
        } finally {
            $this->companySelector->reset();
        }
    }
}
