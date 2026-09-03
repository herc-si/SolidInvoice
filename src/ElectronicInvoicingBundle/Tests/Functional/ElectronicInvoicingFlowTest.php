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

namespace SolidInvoice\ElectronicInvoicingBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ElectronicInvoicingBundle\Action\SendElectronicInvoice;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;
use SolidInvoice\ElectronicInvoicingBundle\Provider\ElectronicInvoiceProviderRegistry;
use SolidInvoice\ElectronicInvoicingBundle\Repository\ElectronicInvoiceSubmissionRepository;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Test\Factory\TaxIdentifierFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(SendElectronicInvoice::class)]
#[CoversClass(ElectronicInvoiceProviderRegistry::class)]
#[CoversClass(ElectronicInvoiceProviderSetting::class)]
final class ElectronicInvoicingFlowTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testSendingElectronicInvoiceWithActiveTestProviderCreatesSuccessfulSubmission(): void
    {
        $invoice = $this->createInvoiceForClientWithSiret();
        $this->configureActiveTestProvider();

        $this->sendElectronicInvoice($invoice);

        $submissions = self::getContainer()->get(ElectronicInvoiceSubmissionRepository::class)->findAll();

        self::assertCount(1, $submissions);
        self::assertTrue($submissions[0]->isSuccess());
        self::assertSame('test_provider', $submissions[0]->getProvider());
        self::assertNotNull($submissions[0]->getExternalReference());
        self::assertStringStartsWith('E2E-', $submissions[0]->getExternalReference());
        self::assertSame($invoice->getId(), $submissions[0]->getInvoice()->getId());
    }

    public function testSendingElectronicInvoiceWithSimulatedFailureCreatesFailedSubmission(): void
    {
        $invoice = $this->createInvoiceForClientWithSiret();
        $this->configureActiveTestProvider(['reference_prefix' => 'E2E', 'simulate_failure' => true]);

        $this->sendElectronicInvoice($invoice);

        $submissions = self::getContainer()->get(ElectronicInvoiceSubmissionRepository::class)->findAll();

        self::assertCount(1, $submissions);
        self::assertFalse($submissions[0]->isSuccess());
        self::assertNull($submissions[0]->getExternalReference());
        self::assertSame('einvoicing.provider.test.simulated_failure', $submissions[0]->getMessage());
    }

    public function testSendingElectronicInvoiceWithoutActiveProviderCreatesNoSubmission(): void
    {
        $invoice = $this->createInvoiceForClientWithSiret();

        $this->sendElectronicInvoice($invoice);

        $submissions = self::getContainer()->get(ElectronicInvoiceSubmissionRepository::class)->findAll();

        self::assertCount(0, $submissions);
    }

    public function testTwoProviderSettingsWithTheSameNameForACompanyAreRejectedByTheValidator(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $first = new ElectronicInvoiceProviderSetting();
        $first->setCompany($this->company)
            ->setName('My Provider')
            ->setProvider('test_provider')
            ->setSettings(['reference_prefix' => 'A']);
        $entityManager->persist($first);
        $entityManager->flush();

        $second = new ElectronicInvoiceProviderSetting();
        $second->setCompany($this->company)
            ->setName('My Provider')
            ->setProvider('test_provider')
            ->setSettings(['reference_prefix' => 'B']);

        $violations = self::getContainer()->get(ValidatorInterface::class)->validate($second);

        self::assertGreaterThan(0, count($violations));
        self::assertSame('einvoicing.constraint.provider_setting.unique_name', $violations[0]->getMessageTemplate());
    }

    private function createInvoiceForClientWithSiret(): Invoice
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'EUR']);

        TaxIdentifierFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'label' => 'SIRET',
            'value' => '12345678900012',
        ]);

        return InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function configureActiveTestProvider(array $settings = ['reference_prefix' => 'E2E']): void
    {
        self::getContainer()->get(SystemConfig::class)->set(SystemConfig::ELECTRONIC_INVOICING_CONFIG_PATH, '1');

        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $providerSetting = new ElectronicInvoiceProviderSetting();
        $providerSetting->setCompany($this->company)
            ->setName('Test Provider')
            ->setProvider('test_provider')
            ->setSettings($settings)
            ->setActive(true);

        $entityManager->persist($providerSetting);
        $entityManager->flush();
    }

    private function sendElectronicInvoice(Invoice $invoice): void
    {
        $action = self::getContainer()->get(SendElectronicInvoice::class);
        $action(Request::createFromGlobals(), $invoice);
    }
}
