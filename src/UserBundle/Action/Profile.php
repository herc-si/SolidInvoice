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

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class Profile
{
    /**
     * @return array<never>
     */
    #[Template('@AugiasUser/Profile/show.html.twig')]
    public function __invoke(TokenStorageInterface $storage): array
    {
        return [];
    }
}
