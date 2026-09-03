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

namespace SolidInvoice\TaxBundle\Validator\Constraints;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class RequiredFiscalIdentifierForElectronicInvoicingValidator extends ConstraintValidator
{
    public function __construct(
        private readonly SystemConfig $systemConfig,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof RequiredFiscalIdentifierForElectronicInvoicing) {
            throw new UnexpectedTypeException($constraint, RequiredFiscalIdentifierForElectronicInvoicing::class);
        }

        if ($value === null) {
            return;
        }

        if (! $value instanceof Client) {
            throw new UnexpectedValueException($value, Client::class);
        }

        if ($this->systemConfig->get(SystemConfig::ELECTRONIC_INVOICING_CONFIG_PATH) !== '1') {
            return;
        }

        foreach ($value->getTaxIdentifiers() as $identifier) {
            if ($identifier->getLabel() === 'SIRET' && ($identifier->getValue() ?? '') !== '') {
                return;
            }
        }

        $this->context->buildViolation($constraint->message)
            ->atPath('taxIdentifiers')
            ->addViolation();
    }
}
