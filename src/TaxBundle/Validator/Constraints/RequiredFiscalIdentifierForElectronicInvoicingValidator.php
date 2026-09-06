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

namespace Augias\TaxBundle\Validator\Constraints;

use Augias\ClientBundle\Entity\Client;
use Augias\SettingsBundle\SystemConfig;
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

        // A pure supplier (isClient false) is never sent an electronic invoice by
        // this company, so it has no need for a SIRET here.
        if (! $value->isClient()) {
            return;
        }

        // Nor does a private individual — the mandatory e-invoicing rules this
        // enforces only apply between VAT-registered businesses.
        if (! $value->isCompany()) {
            return;
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
