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

namespace Augias\ElectronicInvoicingBundle\Action;

use Augias\CoreBundle\Response\FlashResponse;
use Augias\ElectronicInvoicingBundle\Manager\ElectronicInvoiceManagerInterface;
use Augias\InvoiceBundle\Entity\Invoice;
use Generator;
use LogicException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @see \Augias\ElectronicInvoicingBundle\Tests\Action\SendElectronicInvoiceTest
 */
final class SendElectronicInvoice
{
    public function __construct(
        private readonly ElectronicInvoiceManagerInterface $manager,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request, Invoice $invoice): RedirectResponse
    {
        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

        try {
            $submission = $this->manager->send($invoice);
        } catch (LogicException) {
            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield FlashResponse::FLASH_ERROR => 'einvoicing.send.no_active_provider';
                }
            };
        }

        if (! $submission->isSuccess()) {
            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield FlashResponse::FLASH_ERROR => 'einvoicing.send.failed';
                }
            };
        }

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield FlashResponse::FLASH_SUCCESS => 'einvoicing.send.success';
            }
        };
    }
}
