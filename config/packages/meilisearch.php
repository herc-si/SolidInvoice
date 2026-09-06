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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Entity\Contact;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Entity\RecurringInvoice;
use Augias\PaymentBundle\Entity\Payment;
use Augias\QuoteBundle\Entity\Quote;

return App::config([
    'meilisearch' => [
        'url' => env('AUGIAS_MEILISEARCH_URL'),
        'api_key' => env('AUGIAS_MEILISEARCH_API_KEY'),
        'prefix' => env('AUGIAS_MEILISEARCH_PREFIX'),
        'indices' => [
            [
                'name' => 'clients',
                'class' => Client::class,
                'enable_serializer_groups' => true,
                'serializer_groups' => ['searchable'],
                'settings' => [
                    'filterableAttributes' => ['companyId', 'status'],
                    'sortableAttributes' => ['name'],
                ],
            ],
            [
                'name' => 'contacts',
                'class' => Contact::class,
                'enable_serializer_groups' => true,
                'serializer_groups' => ['searchable'],
                'settings' => [
                    'filterableAttributes' => ['companyId', 'clientId'],
                ],
            ],
            [
                'name' => 'invoices',
                'class' => Invoice::class,
                'enable_serializer_groups' => true,
                'serializer_groups' => ['searchable'],
                'settings' => [
                    'filterableAttributes' => ['companyId', 'status', 'total', 'client.name', 'created'],
                    'sortableAttributes' => ['total', 'created'],
                ],
            ],
            [
                'name' => 'recurring_invoices',
                'class' => RecurringInvoice::class,
                'enable_serializer_groups' => true,
                'serializer_groups' => ['searchable'],
                'settings' => [
                    'filterableAttributes' => ['companyId', 'status', 'total', 'client.name'],
                    'sortableAttributes' => ['total', 'created'],
                ],
            ],
            [
                'name' => 'quotes',
                'class' => Quote::class,
                'enable_serializer_groups' => true,
                'serializer_groups' => ['searchable'],
                'settings' => [
                    'filterableAttributes' => ['companyId', 'status', 'total', 'client.name', 'created'],
                    'sortableAttributes' => ['total', 'created'],
                ],
            ],
            [
                'name' => 'payments',
                'class' => Payment::class,
                'enable_serializer_groups' => true,
                'serializer_groups' => ['searchable'],
                'settings' => [
                    'filterableAttributes' => ['companyId', 'status', 'client.name', 'total'],
                    'sortableAttributes' => ['total'],
                ],
            ],
        ],
    ],
]);
