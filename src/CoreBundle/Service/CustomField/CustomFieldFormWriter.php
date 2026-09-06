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

namespace Augias\CoreBundle\Service\CustomField;

use Augias\CoreBundle\Entity\CustomField\CustomFieldValue;
use Augias\CoreBundle\Enum\CustomFieldTarget;
use Augias\CoreBundle\Repository\CustomFieldRepository;
use Augias\CoreBundle\Repository\CustomFieldValueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Writes custom-field values captured by a form (rendered via
 * CustomFieldValueCollectionType with manage_persistence=false) to the
 * database against a now-persisted target record.
 *
 * Caller is responsible for flushing the EntityManager.
 */
final readonly class CustomFieldFormWriter
{
    public function __construct(
        private CustomFieldRepository $fields,
        private CustomFieldValueRepository $values,
        private CustomFieldTypeResolver $resolver,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @param FormInterface<mixed> $customFieldsForm
     */
    public function write(
        FormInterface $customFieldsForm,
        CustomFieldTarget $target,
        Ulid $targetId,
        ?object $companySource = null,
    ): void {
        $defs = $this->fields->findByTargetOrdered($target);
        if ($defs === []) {
            return;
        }

        $existingByFieldId = [];
        foreach ($this->values->findForRecord($target, $targetId) as $existing) {
            $field = $existing->getField();
            if ($field !== null && $field->getId() !== null) {
                $existingByFieldId[(string) $field->getId()] = $existing;
            }
        }

        $company = null;
        if ($companySource !== null && method_exists($companySource, 'getCompany')) {
            try {
                $company = $companySource->getCompany();
            } catch (Error) {
                $company = null;
            }
        }

        foreach ($defs as $def) {
            $fieldKey = $def->getFieldKey();
            if ($fieldKey === null || ! $customFieldsForm->has($fieldKey)) {
                continue;
            }

            $serialized = $this->resolver->serialize($def, $customFieldsForm->get($fieldKey)->getData());
            $existing = $existingByFieldId[(string) $def->getId()] ?? null;

            if ($serialized === null) {
                if ($existing !== null) {
                    $this->em->remove($existing);
                }

                continue;
            }

            if ($existing !== null) {
                $existing->setValue($serialized);
                continue;
            }

            $value = new CustomFieldValue()
                ->setField($def)
                ->setTarget($target)
                ->setTargetId($targetId)
                ->setValue($serialized);
            if ($company !== null) {
                $value->setCompany($company);
            }

            $this->em->persist($value);
        }
    }
}
