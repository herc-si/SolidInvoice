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

return App::config([
    'twig_component' => [
        'anonymous_template_directory' => 'components/',
        'defaults' => [
            'Augias\\ClientBundle\\Twig\\Components\\' => '@AugiasClient/Components',
            'Augias\\CoreBundle\\Twig\\Components\\' => '@AugiasCore/Components',
            'Augias\\DataGridBundle\\Twig\\Components\\' => '@AugiasDataGrid/Components',
            'Augias\\ElectronicInvoicingBundle\\Twig\\Components\\' => '@AugiasElectronicInvoicing/Components',
            'Augias\\InstallBundle\\Twig\\Components\\' => '@AugiasInstall/Components',
            'Augias\\InvoiceBundle\\Twig\\Components\\' => '@AugiasInvoice/Components',
            'Augias\\NotificationBundle\\Twig\\Components\\' => '@AugiasNotification/Components',
            'Augias\\QuoteBundle\\Twig\\Components\\' => '@AugiasQuote/Components',
            'Augias\\SettingsBundle\\Twig\\Components\\' => '@AugiasSettings/Components',
            'Augias\\TaxBundle\\Twig\\Components\\' => '@AugiasTax/Components',
            'Augias\\PaymentBundle\\Twig\\Components\\' => '@AugiasPayment/Components',
            'Augias\\UserBundle\\Twig\\Components\\' => '@AugiasUser/Components',
        ],
    ],
]);
