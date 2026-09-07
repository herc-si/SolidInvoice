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

namespace Augias\TaxBundle\Tests\Service;

use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\SettingsBundle\SystemConfig;
use Augias\TaxBundle\Entity\Tax;
use Augias\TaxBundle\Service\TaxAvailability;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Six places used to ask the repository directly whether any tax rate existed,
 * and that was the only reason the tax field ever hid itself. Being outside the
 * scope of VAT is a second, unrelated reason to hide it, and the two are joined
 * here so those six places cannot drift apart on the answer.
 */
#[CoversClass(TaxAvailability::class)]
final class TaxAvailabilityTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testNotOfferedWhenNoRateIsConfigured(): void
    {
        self::assertFalse($this->availability()->isOffered());
    }

    public function testOfferedOnceARateExists(): void
    {
        $this->createTaxRate();

        self::assertTrue($this->availability()->isOffered());
    }

    /**
     * The point of the change: a company in franchise en base charges no VAT,
     * so the field stays hidden even though a rate is sitting there — left over
     * from before the threshold was crossed, or created by mistake.
     */
    public function testNotOfferedWhenTheCompanyIsOutsideTheScopeOfVat(): void
    {
        $this->createTaxRate();
        $this->setVatExempt(true);

        self::assertFalse($this->availability()->isOffered());
    }

    /**
     * And it comes back if they become liable, without the rate having to be
     * recreated — crossing a threshold mid-year is the normal path, not an edge
     * case.
     */
    public function testOfferedAgainOnceTheCompanyBecomesLiable(): void
    {
        $this->createTaxRate();
        $this->setVatExempt(true);
        $this->setVatExempt(false);

        self::assertTrue($this->availability()->isOffered());
    }

    private function availability(): TaxAvailability
    {
        $availability = self::getContainer()->get(TaxAvailability::class);
        self::assertInstanceOf(TaxAvailability::class, $availability);

        return $availability;
    }

    private function setVatExempt(bool $exempt): void
    {
        self::getContainer()->get(SystemConfig::class)
            ->set(SystemConfig::VAT_EXEMPT_CONFIG_PATH, $exempt ? '1' : '0');
    }

    private function createTaxRate(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $tax = new Tax();
        $tax->setCompany($this->company)->setName('TVA 20')->setRate(20.0)->setType('Exclusive');

        $entityManager->persist($tax);
        $entityManager->flush();
    }
}
