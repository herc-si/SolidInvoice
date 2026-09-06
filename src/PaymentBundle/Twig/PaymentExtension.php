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

namespace Augias\PaymentBundle\Twig;

use Augias\ClientBundle\Entity\Client;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Entity\PaymentMethod;
use Brick\Math\BigInteger;
use Doctrine\Persistence\ManagerRegistry;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PaymentExtension extends AbstractExtension
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('payment_enabled', function (string $method): bool {
                $paymentMethod = $this
                    ->registry
                    ->getRepository(PaymentMethod::class)
                    ->findOneBy(['gatewayName' => $method]);

                if (null === $paymentMethod) {
                    return false;
                }

                return $paymentMethod->isEnabled();
            }),
            new TwigFunction('payments_configured', fn (bool $includeInternal = true): int => $this
                ->registry
                ->getRepository(PaymentMethod::class)
                ->getTotalMethodsConfigured($includeInternal)),
            new TwigFunction('total_income', fn (Client $client): BigInteger => $this
                ->registry
                ->getRepository(Payment::class)
                ->getTotalIncomeForClient($client)),
            new TwigFunction('total_outstanding', fn (Client $client): BigInteger => $this
                ->registry
                ->getRepository(Invoice::class)
                ->getTotalOutstandingForClient($client)),
        ];
    }
}
