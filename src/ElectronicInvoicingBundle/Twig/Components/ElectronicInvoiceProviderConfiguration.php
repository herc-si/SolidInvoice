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

namespace Augias\ElectronicInvoicingBundle\Twig\Components;

use Augias\CoreBundle\Response\FlashResponse;
use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;
use Augias\ElectronicInvoicingBundle\Form\Type\ElectronicInvoiceProviderSettingType;
use Augias\ElectronicInvoicingBundle\Repository\ElectronicInvoiceProviderSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use function assert;

/**
 * @see \Augias\ElectronicInvoicingBundle\Tests\Twig\Components\ElectronicInvoiceProviderConfigurationTest
 */
#[AsLiveComponent]
final class ElectronicInvoiceProviderConfiguration extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    /**
     * @var ElectronicInvoiceProviderSetting|string|null
     */
    #[LiveProp(writable: true, fieldName: 'formData', updateFromParent: true)]
    public $setting = null;

    #[LiveProp(writable: true, updateFromParent: true, url: true)]
    public ?string $provider = null;

    #[LiveProp(writable: true)]
    public ?bool $showDeleteConfirmation = false;

    public function __construct(
        private readonly ElectronicInvoiceProviderSettingRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[ExposeInTemplate]
    public function providerSetting(): ElectronicInvoiceProviderSetting
    {
        if (is_string($this->setting) && $this->setting !== '') {
            $loadedSetting = $this->repository->find(Ulid::fromString($this->setting));

            if (! $loadedSetting instanceof ElectronicInvoiceProviderSetting) {
                throw $this->createNotFoundException(sprintf('Provider setting with ID "%s" not found', $this->setting));
            }

            $this->setting = $loadedSetting;
        }

        if ($this->setting instanceof ElectronicInvoiceProviderSetting) {
            return $this->setting;
        }

        $this->setting = new ElectronicInvoiceProviderSetting();

        if ($this->provider !== null && $this->provider !== '') {
            $this->setting->setProvider($this->provider);
        }

        return $this->setting;
    }

    #[ExposeInTemplate]
    public function isNewSetting(): bool
    {
        return ! $this->providerSetting()->getId() instanceof Ulid;
    }

    /**
     * @return FormInterface<mixed>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ElectronicInvoiceProviderSettingType::class, $this->providerSetting());
    }

    #[LiveAction]
    public function save(): Response
    {
        $this->submitForm();
        $form = $this->getForm();

        if (! $form->isValid()) {
            $this->flash(FlashResponse::FLASH_ERROR, 'einvoicing.provider.flash.validation_errors');

            return $this->redirectToRoute('_einvoicing_providers');
        }

        /** @var ElectronicInvoiceProviderSetting $setting */
        $setting = $form->getData();
        $isNew = $this->isNewSetting();

        // Only one provider is ever "active" (the one invoice sending
        // dispatches to) — the very first one configured for a company
        // becomes active automatically so there is always something to send
        // to as soon as one provider is set up.
        if ($isNew && $this->repository->findActive() === null) {
            $setting->setActive(true);
        }

        $this->entityManager->persist($setting);
        $this->entityManager->flush();

        $this->flash(FlashResponse::FLASH_SUCCESS, $isNew ? 'einvoicing.provider.flash.added' : 'einvoicing.provider.flash.updated');

        return $this->redirectToRoute('_einvoicing_providers');
    }

    #[LiveAction]
    public function showDeleteConfirmation(): void
    {
        $this->showDeleteConfirmation = true;
    }

    #[LiveAction]
    public function cancelDelete(): void
    {
        $this->showDeleteConfirmation = false;
    }

    #[LiveAction]
    public function confirmDelete(): Response
    {
        $setting = $this->providerSetting();

        if ($this->isNewSetting()) {
            $this->flash(FlashResponse::FLASH_ERROR, 'einvoicing.provider.flash.not_exist');

            return $this->redirectToRoute('_einvoicing_providers');
        }

        $wasActive = $setting->isActive();
        $this->entityManager->remove($setting);
        $this->entityManager->flush();

        if ($wasActive) {
            $next = $this->repository->findOneBy([]);

            if ($next instanceof ElectronicInvoiceProviderSetting) {
                $next->setActive(true);
                $this->entityManager->flush();
            }
        }

        $this->flash(FlashResponse::FLASH_INFO, 'einvoicing.provider.flash.deleted');

        return $this->redirectToRoute('_einvoicing_providers');
    }

    private function flash(string $type, string $message): void
    {
        $session = $this->requestStack->getSession();
        assert($session instanceof Session);
        $session->getFlashBag()->add($type, $message);
    }
}
