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

namespace Augias\CoreBundle\Tests\Repository;

use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Repository\CompanyRepository;
use Augias\CoreBundle\Test\Traits\DoctrineTestTrait;
use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Entity\SecurityToken;
use Augias\PaymentBundle\Test\Factory\PaymentFactory;
use Payum\Core\Model\Identity;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(CompanyRepository::class)]
final class CompanyRepositoryTest extends KernelTestCase
{
    use DoctrineTestTrait;

    public function testDeleteCompanyAlsoRemovesOrphanedSecurityTokens(): void
    {
        $companyId = $this->company->getId();
        $payment = PaymentFactory::createOne();

        $token = new SecurityToken();
        $token->setDetails(new Identity($payment->getId()->toString(), $payment));
        $token->setTargetUrl('https://example.com/payment');
        $token->setGatewayName('test_gateway');

        $this->em->persist($token);
        $this->em->flush();
        $this->em->clear();

        /** @var CompanyRepository $companyRepository */
        $companyRepository = $this->em->getRepository(Company::class);

        $companyRepository->deleteCompany($companyId);

        $this->em->clear();

        self::assertNull($this->em->find(Company::class, $companyId));
        self::assertCount(0, $this->em->getRepository(Payment::class)->findAll());
        self::assertCount(0, $this->em->getRepository(SecurityToken::class)->findAll());
    }

    public function testDeleteCompanyWithNoPaymentsDoesNotFail(): void
    {
        $companyId = $this->company->getId();

        /** @var CompanyRepository $companyRepository */
        $companyRepository = $this->em->getRepository(Company::class);

        $companyRepository->deleteCompany($companyId);

        $this->em->clear();

        self::assertNull($this->em->find(Company::class, $companyId));
    }

    public function testDeleteCompanyWithNullIdDoesNothing(): void
    {
        /** @var CompanyRepository $companyRepository */
        $companyRepository = $this->em->getRepository(Company::class);

        $companyRepository->deleteCompany(null);

        self::assertNotNull($this->em->find(Company::class, $this->company->getId()));
    }
}
