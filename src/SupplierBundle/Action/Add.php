<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SupplierBundle\Action;

use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use Symfony\Bridge\Twig\Attribute\Template;

/**
 * Hands a fresh, unpersisted {@see Client} (pre-flagged as a supplier, not a
 * client) to the same `ClientForm` Twig Live Component the "Add Client" page
 * uses — a supplier gets exactly the same contacts/addresses/tax-identifiers
 * UI a client does, since it's the same underlying record.
 */
final class Add
{
    /**
     * @return array{client: Client}
     */
    #[Template('@SolidInvoiceSupplier/Default/add.html.twig')]
    public function __invoke(): array
    {
        $client = new Client();
        $client->addContact(new Contact())
            ->addAddress(new Address())
            ->setIsClient(false)
            ->setIsSupplier(true);

        return [
            'client' => $client,
        ];
    }
}
