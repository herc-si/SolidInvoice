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

namespace SolidInvoice\ElectronicInvoicingBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceSubmission;
use SolidInvoice\ElectronicInvoicingBundle\Provider\ElectronicInvoiceProviderRegistry;
use SolidInvoice\ElectronicInvoicingBundle\Repository\ElectronicInvoiceProviderSettingRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\SettingsBundle\SystemConfig;

/**
 * Central place for "can this invoice be sent electronically, and doing so" —
 * shared by the explicit "Send electronic invoice" action and the automatic
 * trigger fired when an invoice is published (see InvoiceBundle\Action\Transition\Send),
 * so eligibility and submission bookkeeping stay in one place.
 *
 * @see \SolidInvoice\ElectronicInvoicingBundle\Tests\Manager\ElectronicInvoiceManagerTest
 */
final readonly class ElectronicInvoiceManager implements ElectronicInvoiceManagerInterface
{
    public function __construct(
        private SystemConfig $systemConfig,
        private ElectronicInvoiceProviderRegistry $registry,
        private ElectronicInvoiceProviderSettingRepository $settingRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function isEligible(Invoice $invoice): bool
    {
        if ($this->systemConfig->get(SystemConfig::ELECTRONIC_INVOICING_CONFIG_PATH) !== '1') {
            return false;
        }

        if (! $this->registry->hasActiveProvider()) {
            return false;
        }

        return $this->clientHasSiret($invoice);
    }

    public function send(Invoice $invoice): ElectronicInvoiceSubmission
    {
        $activeSetting = $this->settingRepository->findActive();

        $result = $this->registry->send($invoice);

        $submission = new ElectronicInvoiceSubmission();
        $submission->setInvoice($invoice)
            ->setProvider($activeSetting instanceof ElectronicInvoiceProviderSetting ? $activeSetting->getProvider() : '')
            ->setSuccess($result->success)
            ->setExternalReference($result->externalReference)
            ->setMessage($result->message);

        $this->entityManager->persist($submission);
        $this->entityManager->flush();

        return $submission;
    }

    private function clientHasSiret(Invoice $invoice): bool
    {
        $client = $invoice->getClient();

        if ($client === null) {
            return false;
        }

        foreach ($client->getTaxIdentifiers() as $identifier) {
            if ($identifier->getLabel() === 'SIRET' && $identifier->getValue() !== null && $identifier->getValue() !== '') {
                return true;
            }
        }

        return false;
    }
}
