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

namespace Augias\UserBundle\Action;

use Augias\ApiBundle\Security\Attribute as ApiAttribute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ApiIndex extends AbstractController
{
    public function __invoke(): Response
    {
        // Delegates to the API access voter: SubscriptionVoter (paid-only) on SaaS,
        // ApiAccessVoter (feature-only) on self-hosted.
        if (! $this->isGranted(ApiAttribute::ACCESS)) {
            return $this->render('@AugiasUser/Api/gated.html.twig');
        }

        return $this->render('@AugiasUser/Api/index.html.twig');
    }
}
