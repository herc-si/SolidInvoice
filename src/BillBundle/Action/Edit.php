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
use Augias\BillBundle\Form\Type\BillType;
use Augias\ClientBundle\Entity\Client;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currency;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use function assert;

final readonly class Edit
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private ManagerRegistry $doctrine,
    ) {
    }

    /**
     * @return array{form: FormView, bill: Bill}|Response
     */
    #[Template('@AugiasBill/Default/form.html.twig')]
    public function __invoke(Request $request, Bill $bill): array | Response
    {
        $form = $this->formFactory->create(BillType::class, $bill, [
            'currency' => new Currency($bill->getCurrencyCode()),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();

            /** @var Client|null $supplier */
            $supplier = $form->get('supplier')->getData();
            $newSupplierName = trim((string) $form->get('newSupplierName')->getData());

            if (! $supplier instanceof Client && $newSupplierName !== '') {
                $supplier = new Client();
                $supplier->setName($newSupplierName)
                    ->setIsClient(false)
                    ->setIsSupplier(true);
                $entityManager->persist($supplier);
            }

            // The form-level SUBMIT listener already rejected the case where
            // neither field was filled, so $supplier is guaranteed here.
            $bill->setSupplier($supplier);

            $entityManager->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'bill.edit.success');

            return new RedirectResponse($this->router->generate('_bills_view', ['id' => $bill->getId()]));
        }

        return [
            'form' => $form->createView(),
            'bill' => $bill,
        ];
    }
}
