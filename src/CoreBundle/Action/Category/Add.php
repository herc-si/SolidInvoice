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

namespace Augias\CoreBundle\Action\Category;

use Augias\CoreBundle\Entity\Category;
use Augias\CoreBundle\Enum\CategoryUsage;
use Augias\CoreBundle\Form\Type\CategoryType;
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

final readonly class Add
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private ManagerRegistry $doctrine,
    ) {
    }

    /**
     * @return array{form: FormView}|Response
     */
    #[Template('@AugiasCore/Category/form.html.twig')]
    public function __invoke(Request $request): array | Response
    {
        $category = new Category();

        // Pre-tick the usage the user came from, so adding a category while
        // filling in a supplier invoice does not silently create one that the
        // purchase dropdown will not offer.
        $usage = CategoryUsage::tryFrom((string) $request->query->get('usage', ''));

        if ($usage instanceof CategoryUsage) {
            $category->setUsage($usage, true);
        }

        $form = $this->formFactory->create(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($category);
            $entityManager->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'category.create.success');

            return new RedirectResponse($this->router->generate('_categories_index'));
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
