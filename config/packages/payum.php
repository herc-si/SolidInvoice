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

use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Entity\PaymentMethod;
use Augias\PaymentBundle\Entity\SecurityToken;
use Augias\PaymentBundle\Form\Methods\AuthorizeNetAim;
use Augias\PaymentBundle\Form\Methods\Be2billDirect;
use Augias\PaymentBundle\Form\Methods\Be2billOffsite;
use Augias\PaymentBundle\Form\Methods\KlarnaCheckout;
use Augias\PaymentBundle\Form\Methods\KlarnaInvoice;
use Augias\PaymentBundle\Form\Methods\Payex;
use Augias\PaymentBundle\Form\Methods\PaypalExpressCheckout;
use Augias\PaymentBundle\Form\Methods\PaypalProCheckout;
use Augias\PaymentBundle\Form\Methods\StripeCheckout;
use Augias\PaymentBundle\Form\Methods\StripeJs;

return App::config([
    'parameters' => [
        'payum.template.layout' => '@AugiasPayment/layout.html.twig',
    ],
    'payum' => [
        'security' => [
            'token_storage' => [
                SecurityToken::class => [
                    'doctrine' => 'orm',
                ],
            ],
        ],
        'storages' => [
            Payment::class => [
                'doctrine' => 'orm',
            ],
        ],
        'dynamic_gateways' => [
            'sonata_admin' => false,
            'config_storage' => [
                PaymentMethod::class => [
                    'doctrine' => 'orm',
                ],
            ],
        ],
    ],
    'payment' => [
        'gateways' => [
            ['name' => 'credit', 'factory' => 'offline'],
            ['name' => 'custom', 'factory' => 'offline'],
            ['name' => 'cash', 'factory' => 'offline'],
            ['name' => 'bank_transfer', 'factory' => 'offline'],
            ['name' => 'paypal_express_checkout', 'factory' => 'paypal_express_checkout', 'form' => PaypalExpressCheckout::class],
            ['name' => 'paypal_pro_checkout', 'factory' => 'paypal_pro_checkout', 'form' => PaypalProCheckout::class],
            ['name' => 'stripe_checkout', 'factory' => 'stripe_checkout', 'form' => StripeCheckout::class],
            ['name' => 'stripe_js', 'factory' => 'stripe_js', 'form' => StripeJs::class],
            ['name' => 'klarna_invoice', 'factory' => 'klarna_invoice', 'form' => KlarnaInvoice::class],
            ['name' => 'klarna_checkout', 'factory' => 'klarna_checkout', 'form' => KlarnaCheckout::class],
            ['name' => 'be2bill_offsite', 'factory' => 'be2bill_offsite', 'form' => Be2billOffsite::class],
            ['name' => 'be2bill_direct', 'factory' => 'be2bill_direct', 'form' => Be2billDirect::class],
            ['name' => 'authorize_net_aim', 'factory' => 'authorize_net_aim', 'form' => AuthorizeNetAim::class],
            ['name' => 'payex', 'factory' => 'payex', 'form' => Payex::class],
        ],
    ],
]);
