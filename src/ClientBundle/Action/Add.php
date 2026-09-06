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

namespace Augias\ClientBundle\Action;

use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Form\Type\ClientType;
use Augias\ClientBundle\Repository\ClientRepository;
use Augias\SaasBundle\Feature\Feature;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use function assert;

final class Add extends AbstractController
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly RouterInterface $router,
        private readonly ManagerRegistry $doctrine,
        private readonly ClientRepository $clientRepository,
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (! $this->featureGate->canUse(Feature::TotalClients->value, $this->clientRepository->getTotalClients())) {
            return $this->render('@AugiasClient/Default/gated.html.twig');
        }

        $client = new Client();
        $form = $this->formFactory->create(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($client);
            $entityManager->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'client.create.success');

            return new RedirectResponse($this->router->generate('_clients_view', ['id' => $client->getId()]));
        }

        return $this->render('@AugiasClient/Default/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
