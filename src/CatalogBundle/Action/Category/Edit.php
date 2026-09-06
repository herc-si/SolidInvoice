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

namespace SolidInvoice\CatalogBundle\Action\Category;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CatalogBundle\Entity\ProductCategory;
use SolidInvoice\CatalogBundle\Form\Type\ProductCategoryFormType;
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
     * @return array{form: FormView, category?: ProductCategory}|Response
     */
    #[Template('@SolidInvoiceCatalog/Category/form.html.twig')]
    public function __invoke(Request $request, ProductCategory $category): array | Response
    {

        $form = $this->formFactory->create(ProductCategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();

            $entityManager->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'catalog.category.action.updated');

            return new RedirectResponse($this->router->generate('_catalog_categories_index'));
        }

        return ['form' => $form->createView(), 'category' => $category];
    }
}
