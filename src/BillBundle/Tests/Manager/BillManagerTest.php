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

namespace Augias\BillBundle\Tests\Manager;

use Augias\BillBundle\Enum\BillStatus;
use Augias\BillBundle\Manager\BillManager;
use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Repository\ClientRepository;
use Augias\CoreBundle\Entity\Company;
use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceReceipt;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BillManager::class)]
final class BillManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreateFromReceiptCopiesFieldsAndStartsPending(): void
    {
        $company = new Company();
        $receipt = new ElectronicInvoiceReceipt();
        $receipt->setCompany($company)
            ->setProvider('super_pdp')
            ->setExternalReference('555')
            ->setInvoiceNumber('SUP-0042')
            ->setSellerName('Acme Supplies')
            ->setSellerIdentifier('111222333')
            ->setIssueDate(new DateTimeImmutable('2026-01-01'))
            ->setTotalAmount(BigInteger::of(19990))
            ->setCurrencyCode('EUR');

        $clientRepository = M::mock(ClientRepository::class);
        $clientRepository->shouldReceive('findOneByTaxIdentifierValue')
            ->with($company->getId(), '111222333')
            ->andReturnNull();
        $clientRepository->shouldReceive('findOneByName')
            ->with($company->getId(), 'Acme Supplies')
            ->andReturnNull();

        $entityManager = M::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('persist')->twice();
        $entityManager->shouldReceive('flush')->once();

        $manager = new BillManager($entityManager, $clientRepository);

        $bill = $manager->createFromReceipt($receipt);

        self::assertSame($company, $bill->getCompany());
        self::assertSame(BillStatus::Pending, $bill->getStatus());
        self::assertSame('SUP-0042', $bill->getBillNumber());
        self::assertSame('19990', (string) $bill->getTotalAmount());
        self::assertSame('EUR', $bill->getCurrencyCode());
        self::assertSame($receipt, $bill->getElectronicInvoiceReceipt());
        self::assertSame('Acme Supplies', $bill->getSupplier()->getName());
        self::assertFalse($bill->getSupplier()->isClient());
        self::assertTrue($bill->getSupplier()->isSupplier());
        self::assertSame('111222333', $bill->getSupplier()->getTaxIdentifiers()->first()->getValue());
    }

    public function testCreateFromReceiptReusesASupplierMatchedByTaxIdentifier(): void
    {
        $company = new Company();
        $existingSupplier = new Client();
        $existingSupplier->setName('Acme Supplies');

        $receipt = new ElectronicInvoiceReceipt();
        $receipt->setCompany($company)
            ->setProvider('super_pdp')
            ->setExternalReference('555')
            ->setSellerName('Acme Supplies')
            ->setSellerIdentifier('111222333');

        $clientRepository = M::mock(ClientRepository::class);
        $clientRepository->shouldReceive('findOneByTaxIdentifierValue')
            ->with($company->getId(), '111222333')
            ->andReturn($existingSupplier);

        $entityManager = M::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('persist')->once();
        $entityManager->shouldReceive('flush')->once();

        $manager = new BillManager($entityManager, $clientRepository);

        $bill = $manager->createFromReceipt($receipt);

        self::assertSame($existingSupplier, $bill->getSupplier());
        self::assertTrue($bill->getSupplier()->isSupplier());
    }

    public function testCreateFromReceiptFallsBackToMatchingByNameWithoutATaxIdentifier(): void
    {
        $company = new Company();
        $existingSupplier = new Client();
        $existingSupplier->setName('Acme Supplies');

        $receipt = new ElectronicInvoiceReceipt();
        $receipt->setCompany($company)
            ->setProvider('super_pdp')
            ->setExternalReference('555')
            ->setSellerName('Acme Supplies');

        $clientRepository = M::mock(ClientRepository::class);
        $clientRepository->shouldReceive('findOneByName')
            ->with($company->getId(), 'Acme Supplies')
            ->andReturn($existingSupplier);

        $entityManager = M::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('persist')->once();
        $entityManager->shouldReceive('flush')->once();

        $manager = new BillManager($entityManager, $clientRepository);

        $bill = $manager->createFromReceipt($receipt);

        self::assertSame($existingSupplier, $bill->getSupplier());
        self::assertTrue($bill->getSupplier()->isSupplier());
    }

    public function testCreateFromReceiptDefaultsAnUnnamedSellerToUnknownSupplier(): void
    {
        $company = new Company();
        $receipt = new ElectronicInvoiceReceipt();
        $receipt->setCompany($company)
            ->setProvider('super_pdp')
            ->setExternalReference('555');

        $clientRepository = M::mock(ClientRepository::class);
        $clientRepository->shouldReceive('findOneByName')
            ->andReturnNull();

        $entityManager = M::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('persist')->twice();
        $entityManager->shouldReceive('flush')->once();

        $manager = new BillManager($entityManager, $clientRepository);

        $bill = $manager->createFromReceipt($receipt);

        self::assertSame('Unknown supplier', $bill->getSupplier()->getName());
    }

    public function testCreateFromReceiptDefaultsMissingAmountAndCurrency(): void
    {
        $company = new Company();
        $receipt = new ElectronicInvoiceReceipt();
        $receipt->setCompany($company)
            ->setProvider('super_pdp')
            ->setExternalReference('555');

        $clientRepository = M::mock(ClientRepository::class);
        $clientRepository->shouldReceive('findOneByName')
            ->andReturnNull();

        $entityManager = M::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('persist')->twice();
        $entityManager->shouldReceive('flush')->once();

        $manager = new BillManager($entityManager, $clientRepository);

        $bill = $manager->createFromReceipt($receipt);

        self::assertSame('0', (string) $bill->getTotalAmount());
        self::assertSame('EUR', $bill->getCurrencyCode());
    }
}
