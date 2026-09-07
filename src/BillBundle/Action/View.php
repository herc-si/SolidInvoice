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

namespace Augias\BillBundle\Action;

use Augias\BillBundle\Entity\Bill;
use Augias\BillBundle\Entity\BillPayment;
use Augias\BillBundle\Form\Type\BillPaymentType;
use Money\Currency;
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
    #[Template('@AugiasBill/Default/view.html.twig')]
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
