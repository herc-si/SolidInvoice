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

namespace Augias\SettingsBundle\Action\CustomField;

use Augias\CoreBundle\Entity\CustomField\CustomField;
use Augias\CoreBundle\Enum\CustomFieldTarget;
use Augias\CoreBundle\Repository\CustomFieldRepository;
use Augias\CoreBundle\Repository\CustomFieldValueRepository;
use Augias\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class IndexAction extends AbstractController
{
    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function __invoke(): Response
    {
        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            return $this->render('@AugiasSettings/CustomField/gated.html.twig');
        }

        return $this->render('@AugiasSettings/CustomField/index.html.twig', [
            'client' => $this->buildRows(CustomFieldTarget::CLIENT),
            'contact' => $this->buildRows(CustomFieldTarget::CONTACT),
            'invoice' => $this->buildRows(CustomFieldTarget::INVOICE),
            'quote' => $this->buildRows(CustomFieldTarget::QUOTE),
        ]);
    }

    /**
     * @return list<array{field: CustomField, count: int}>
     */
    private function buildRows(CustomFieldTarget $target): array
    {
        $fields = $this->fields->findByTargetOrdered($target);
        if ($fields === []) {
            return [];
        }

        $counts = $this->values->countByFields($fields);

        $rows = [];
        foreach ($fields as $field) {
            $rows[] = [
                'field' => $field,
                'count' => $counts[(string) $field->getId()] ?? 0,
            ];
        }

        return $rows;
    }
}
