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

namespace SolidInvoice\BillBundle\Action;

use Money\Currency;
use SolidInvoice\BillBundle\Entity\Bill;
use SolidInvoice\BillBundle\Entity\BillPayment;
use SolidInvoice\BillBundle\Form\Type\BillPaymentType;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormFactoryInterface;

final readonly class View
{
    public function __construct(
        private FormFactoryInterface $formFactory,
    ) {
    }

    /**
     * @return array{bill: Bill, payment_form: \Symfony\Component\Form\FormView}
     */
    #[Template('@SolidInvoiceBill/Default/view.html.twig')]
    public function __invoke(Bill $bill): array
    {
        $paymentForm = $this->formFactory->create(BillPaymentType::class, new BillPayment(), [
            'currency' => new Currency($bill->getCurrencyCode()),
            'action' => '',
        ]);

        return [
            'bill' => $bill,
            'payment_form' => $paymentForm->createView(),
        ];
    }
}
