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

namespace Augias\ClientBundle\Twig\Components;

use Augias\ClientBundle\Entity\Address;
use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Entity\Contact;
use Augias\ClientBundle\Form\Type\ClientType;
use Augias\CoreBundle\Enum\CustomFieldTarget;
use Augias\CoreBundle\Service\CustomField\CustomFieldFormWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

/**
 * @see \Augias\ClientBundle\Tests\Twig\Components\ClientFormTest
 */
#[AsLiveComponent]
class ClientForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Client $client = null;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly CustomFieldFormWriter $customFieldFormWriter,
    ) {
    }

    /**
     * @return FormInterface<mixed>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            ClientType::class,
            $this->client ?? new Client()
                ->addContact(new Contact())
                ->addAddress(new Address()),
            ['validation_groups' => ['Default', 'form']]
        );
    }

    #[LiveAction]
    public function save(): RedirectResponse | null
    {
        $this->submitForm();

        if (! $this->getForm()->isValid()) {
            return null;
        }

        /** @var Client $client */
        $client = $this->getForm()->getData();
        foreach ($client->getAddresses() as $address) {
            if ($address->isEmpty()) {
                $client->removeAddress($address);
            }
        }

        $this->manager->persist($client);
        $this->manager->flush();

        $form = $this->getForm();

        if ($form->has('customFields')) {
            $this->customFieldFormWriter->write(
                $form->get('customFields'),
                CustomFieldTarget::CLIENT,
                $client->getId(),
                $client,
            );
        }

        foreach ($form->get('contacts') as $contactForm) {
            if ($contactForm->has('customFields')) {
                /** @var Contact $contact */
                $contact = $contactForm->getData();
                $this->customFieldFormWriter->write(
                    $contactForm->get('customFields'),
                    CustomFieldTarget::CONTACT,
                    $contact->getId(),
                    $contact,
                );
            }
        }

        $this->manager->flush();

        $this->addFlash('success', 'client.create.success');
        return $this->redirectToRoute('_clients_view', [
            'id' => $client->getId(),
        ]);
    }
}
