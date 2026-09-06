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

namespace Augias\TaxBundle\Action;

use Augias\TaxBundle\Entity\Tax;
use Augias\TaxBundle\Form\Type\TaxType;
use Doctrine\Persistence\ManagerRegistry;
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
        private ManagerRegistry $doctrine
    ) {
    }

    /**
     * @return array{form: FormView}|Response
     */
    #[Template('@AugiasTax/Default/form.html.twig')]
    public function __invoke(Request $request, Tax $tax): array | Response
    {
        $form = $this->formFactory->create(TaxType::class, $tax);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'Tax rate successfully saved');

            return new RedirectResponse($this->router->generate('_tax_rates'));
        }

        return ['form' => $form->createView()];
    }
}
