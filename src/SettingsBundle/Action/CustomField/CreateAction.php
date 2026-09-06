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
use Augias\CoreBundle\Enum\CustomFieldType;
use Augias\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class CreateAction extends AbstractController
{
    public function __construct(
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function __invoke(): Response
    {
        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            return $this->render('@AugiasSettings/CustomField/gated.html.twig');
        }

        $field = new CustomField()->setType(CustomFieldType::TEXT);

        return $this->render('@AugiasSettings/CustomField/edit.html.twig', [
            'field' => $field,
            'mode' => 'create',
        ]);
    }
}
