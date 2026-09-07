<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\SupplierBundle\Action;

use Augias\ClientBundle\Entity\Client;
use Symfony\Bridge\Twig\Attribute\Template;

/**
 * Editing a supplier is editing the underlying {@see Client} — reuses
 * ClientBundle's own edit template/`ClientForm` component directly rather
 * than duplicating it.
 */
final class Edit
{
    /**
     * @return array{client: Client}
     */
    #[Template('@AugiasClient/Default/edit.html.twig')]
    public function __invoke(Client $client): array
    {
        return [
            'client' => $client,
        ];
    }
}
