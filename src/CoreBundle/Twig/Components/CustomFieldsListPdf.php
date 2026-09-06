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

namespace Augias\CoreBundle\Twig\Components;

use Augias\CoreBundle\Entity\CustomField\CustomField;
use Augias\CoreBundle\Enum\CustomFieldTarget;
use Augias\CoreBundle\Enum\CustomFieldVisibility;
use Augias\CoreBundle\Repository\CustomFieldRepository;
use Augias\CoreBundle\Repository\CustomFieldValueRepository;
use Augias\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Augias\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('CustomFieldsListPdf', template: '@AugiasCore/Components/_custom-fields-pdf.html.twig')]
final class CustomFieldsListPdf
{
    public CustomFieldTarget $target;

    public Ulid $recordId;

    public ?CustomFieldVisibility $visibility = null;

    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
        private readonly CustomFieldTypeResolver $resolver,
        private readonly FeatureGate $featureGate,
    ) {
    }

    /**
     * @return list<array{field: CustomField, formatted: string, raw: ?string}>
     */
    public function getRows(): array
    {
        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            return [];
        }

        $defs = $this->fields->findByTargetOrdered($this->target);
        if ($this->visibility instanceof CustomFieldVisibility) {
            $defs = array_values(array_filter(
                $defs,
                fn (CustomField $f): bool => $f->getVisibility() === $this->visibility,
            ));
        }

        if ($defs === []) {
            return [];
        }

        $byField = [];
        foreach ($this->values->findForRecord($this->target, $this->recordId) as $v) {
            $field = $v->getField();
            if ($field === null) {
                continue;
            }

            $fieldId = $field->getId();
            if ($fieldId === null) {
                continue;
            }

            $byField[(string) $fieldId] = $v;
        }

        $out = [];
        foreach ($defs as $def) {
            $defId = $def->getId();
            $value = $defId !== null ? ($byField[(string) $defId] ?? null) : null;
            $stored = $value?->getValue();
            $out[] = [
                'field' => $def,
                'formatted' => $this->resolver->formatForDisplay($def, $stored),
                'raw' => $stored,
            ];
        }

        return $out;
    }
}
