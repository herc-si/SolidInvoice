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

namespace Augias\PaymentBundle\Action;

use Augias\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class Settings extends AbstractController
{
    public function __construct(
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function __invoke(): Response
    {
        if (! $this->featureGate->isEnabled(Feature::OnlinePayments->value)) {
            return $this->render('@AugiasPayment/Settings/gated.html.twig');
        }

        return $this->render('@AugiasPayment/Settings/index.html.twig');
    }
}
