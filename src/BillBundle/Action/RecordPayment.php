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
use SolidInvoice\BillBundle\Manager\BillPaymentManager;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use function assert;

final readonly class RecordPayment
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private BillPaymentManager $billPaymentManager,
    ) {
    }

    /**
     * @return array{bill: Bill, payment_form: FormView}|Response
     */
    #[Template('@SolidInvoiceBill/Default/view.html.twig')]
    public function __invoke(Request $request, Bill $bill): array | Response
    {
        $payment = new BillPayment();
        $form = $this->formFactory->create(BillPaymentType::class, $payment, [
            'currency' => new Currency($bill->getCurrencyCode()),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->billPaymentManager->recordPayment(
                $bill,
                $payment->getAmount(),
                $payment->getPaidDate(),
                $payment->getMethod(),
                $payment->getReference(),
                $payment->getNotes(),
            );

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'bill.payment.create.success');

            return new RedirectResponse($this->router->generate('_bills_view', ['id' => $bill->getId()]));
        }

        return [
            'bill' => $bill,
            'payment_form' => $form->createView(),
        ];
    }
}
