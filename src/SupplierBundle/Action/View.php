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

namespace Augias\SupplierBundle\Action;

use Augias\ClientBundle\Entity\Client;
use Symfony\Bridge\Twig\Attribute\Template;

final class View
{
    /**
     * @return array{client: Client, bill_grid_context: array{supplier_id: string}}
     */
    #[Template('@AugiasSupplier/Default/view.html.twig')]
    public function __invoke(Client $client): array
    {
        return [
            'client' => $client,
            // Filters BillBundle's bill_grid to this supplier — passed as a plain
            // context array (the DataGrid convention, e.g. PaymentsGrid's
            // client_id/invoice_id context) so SupplierBundle never needs to
            // depend on BillBundle's classes directly.
            'bill_grid_context' => ['supplier_id' => (string) $client->getId()],
        ];
    }
}
