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

namespace Augias\TaxBundle\Tests\Validator\Constraints;

use Augias\TaxBundle\Entity\LineTax;
use Augias\TaxBundle\Entity\Tax;
use Augias\TaxBundle\Enum\TaxType;
use Augias\TaxBundle\Validator\Constraints\IncompatibleTaxConfiguration;
use Augias\TaxBundle\Validator\Constraints\IncompatibleTaxConfigurationValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<IncompatibleTaxConfigurationValidator>
 */
final class IncompatibleTaxConfigurationValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): IncompatibleTaxConfigurationValidator
    {
        return new IncompatibleTaxConfigurationValidator();
    }

    public function testNonCompoundExclusivePasses(): void
    {
        $tax = new Tax()
            ->setType(Tax::TYPE_EXCLUSIVE)
            ->setCompound(false);

        $this->validator->validate($tax, new IncompatibleTaxConfiguration());

        $this->assertNoViolation();
    }

    public function testCompoundExclusivePasses(): void
    {
        $tax = new Tax()
            ->setType(Tax::TYPE_EXCLUSIVE)
            ->setCompound(true);

        $this->validator->validate($tax, new IncompatibleTaxConfiguration());

        $this->assertNoViolation();
    }

    public function testCompoundInclusiveOnTaxRaisesViolation(): void
    {
        $tax = new Tax()
            ->setType(Tax::TYPE_INCLUSIVE)
            ->setCompound(true);

        $constraint = new IncompatibleTaxConfiguration();
        $this->validator->validate($tax, $constraint);

        $this->buildViolation($constraint->inclusiveCompoundMessage)
            ->atPath('property.path.compound')
            ->assertRaised();
    }

    public function testCompoundFlatRateOnTaxRaisesViolation(): void
    {
        $tax = new Tax()
            ->setType(Tax::TYPE_FLAT_RATE)
            ->setCompound(true);

        $constraint = new IncompatibleTaxConfiguration();
        $this->validator->validate($tax, $constraint);

        $this->buildViolation($constraint->flatRateCompoundMessage)
            ->atPath('property.path.compound')
            ->assertRaised();
    }

    public function testCompoundInclusiveOnLineTaxRaisesViolation(): void
    {
        $lineTax = new LineTax()
            ->setNameSnapshot('VAT')
            ->setTypeSnapshot(TaxType::Inclusive)
            ->setCompound(true);

        $constraint = new IncompatibleTaxConfiguration();
        $this->validator->validate($lineTax, $constraint);

        $this->buildViolation($constraint->inclusiveCompoundMessage)
            ->atPath('property.path.compound')
            ->assertRaised();
    }

    public function testCompoundFlatRateOnLineTaxRaisesViolation(): void
    {
        $lineTax = new LineTax()
            ->setNameSnapshot('Stamp')
            ->setTypeSnapshot(TaxType::FlatRate)
            ->setCompound(true);

        $constraint = new IncompatibleTaxConfiguration();
        $this->validator->validate($lineTax, $constraint);

        $this->buildViolation($constraint->flatRateCompoundMessage)
            ->atPath('property.path.compound')
            ->assertRaised();
    }
}
