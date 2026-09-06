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

use Augias\ClientBundle\Entity\Client;
use Augias\SettingsBundle\SystemConfig;
use Augias\TaxBundle\Entity\TaxIdentifier;
use Augias\TaxBundle\Validator\Constraints\RequiredFiscalIdentifierForElectronicInvoicing;
use Augias\TaxBundle\Validator\Constraints\RequiredFiscalIdentifierForElectronicInvoicingValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<RequiredFiscalIdentifierForElectronicInvoicingValidator>
 */
#[CoversClass(RequiredFiscalIdentifierForElectronicInvoicing::class)]
#[CoversClass(RequiredFiscalIdentifierForElectronicInvoicingValidator::class)]
final class RequiredFiscalIdentifierForElectronicInvoicingValidatorTest extends ConstraintValidatorTestCase
{
    private MockObject & SystemConfig $systemConfig;

    protected function createValidator(): RequiredFiscalIdentifierForElectronicInvoicingValidator
    {
        $this->systemConfig = $this->createMock(SystemConfig::class);

        return new RequiredFiscalIdentifierForElectronicInvoicingValidator($this->systemConfig);
    }

    public function testSkipsNullValue(): void
    {
        $this->systemConfig->expects($this->never())->method('get');

        $this->validator->validate(null, new RequiredFiscalIdentifierForElectronicInvoicing());

        $this->assertNoViolation();
    }

    public function testSkipsAPureSupplier(): void
    {
        $this->systemConfig->expects($this->never())->method('get');

        $client = new Client();
        $client->setIsClient(false)->setIsSupplier(true);

        $this->validator->validate($client, new RequiredFiscalIdentifierForElectronicInvoicing());

        $this->assertNoViolation();
    }

    public function testSkipsAnIndividual(): void
    {
        $this->systemConfig->expects($this->never())->method('get');

        $client = new Client();
        $client->setIsCompany(false);

        $this->validator->validate($client, new RequiredFiscalIdentifierForElectronicInvoicing());

        $this->assertNoViolation();
    }

    public function testSkipsWhenElectronicInvoicingIsDisabled(): void
    {
        $this->systemConfig->expects($this->once())->method('get')
            ->with(SystemConfig::ELECTRONIC_INVOICING_CONFIG_PATH)
            ->willReturn('0');

        $client = new Client();

        $this->validator->validate($client, new RequiredFiscalIdentifierForElectronicInvoicing());

        $this->assertNoViolation();
    }

    public function testNoViolationWithASiretIdentifier(): void
    {
        $this->systemConfig->expects($this->once())->method('get')
            ->with(SystemConfig::ELECTRONIC_INVOICING_CONFIG_PATH)
            ->willReturn('1');

        $client = new Client();
        $identifier = new TaxIdentifier();
        $identifier->setLabel('SIRET')->setValue('12345678900012');
        $client->addTaxIdentifier($identifier);

        $this->validator->validate($client, new RequiredFiscalIdentifierForElectronicInvoicing());

        $this->assertNoViolation();
    }

    public function testViolationForACompanyWithoutASiretWhenElectronicInvoicingIsEnabled(): void
    {
        $constraint = new RequiredFiscalIdentifierForElectronicInvoicing();

        $this->systemConfig->expects($this->once())->method('get')
            ->with(SystemConfig::ELECTRONIC_INVOICING_CONFIG_PATH)
            ->willReturn('1');

        $client = new Client();

        $this->validator->validate($client, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.taxIdentifiers')
            ->assertRaised();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWrongConstraintTypeThrows(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Client(), $this->createStub(Constraint::class));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWrongValueTypeThrows(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not-a-client', new RequiredFiscalIdentifierForElectronicInvoicing());
    }
}
