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

namespace Augias\TaxBundle\Tests\Repository;

use Augias\CoreBundle\Company\CompanySelector;
use Augias\CoreBundle\Test\Factory\CompanyFactory;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\TaxBundle\Entity\InvoiceTax;
use Augias\TaxBundle\Repository\InvoiceTaxRepository;
use Augias\TaxBundle\Test\Factory\InvoiceTaxFactory;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(InvoiceTaxRepository::class)]
final class InvoiceTaxRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private InvoiceTaxRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');

        $this->repository = new InvoiceTaxRepository($registry);
    }

    public function testCompanyFilterScopesQueriesByActiveCompany(): void
    {
        InvoiceTaxFactory::createMany(2, ['company' => $this->company]);

        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        InvoiceTaxFactory::createMany(3, ['company' => $otherCompany]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $results = $this->repository->findAll();

        self::assertCount(2, $results);
        foreach ($results as $invoiceTax) {
            self::assertInstanceOf(InvoiceTax::class, $invoiceTax);
            self::assertSame($this->company->getId()->toRfc4122(), $invoiceTax->getCompany()->getId()->toRfc4122());
        }
    }
}
