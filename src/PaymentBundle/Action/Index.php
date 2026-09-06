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

use Augias\PaymentBundle\Manager\PaymentStats;
use Brick\Math\Exception\MathException;
use DateMalformedStringException;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;

final readonly class Index
{
    public function __construct(
        private PaymentStats $paymentStats
    ) {
    }

    /**
     * @return array{stats: array<string, mixed>}
     * @throws MathException
     * @throws DateMalformedStringException
     */
    #[Template('@AugiasPayment/Default/index.html.twig')]
    public function __invoke(Request $request): array
    {
        return [
            'stats' => $this->paymentStats->getStatistics(),
        ];
    }
}
