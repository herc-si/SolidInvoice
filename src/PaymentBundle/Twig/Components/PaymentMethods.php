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

namespace Augias\PaymentBundle\Twig\Components;

use Augias\PaymentBundle\Entity\PaymentMethod;
use Augias\PaymentBundle\Factory\PaymentFactories;
use Augias\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PreMount;
use function array_keys;

/**
 * @see \Augias\PaymentBundle\Tests\Twig\Components\PaymentMethodsTest
 */
#[AsLiveComponent]
final class PaymentMethods extends AbstractController
{
    use DefaultActionTrait;

    public function __construct(
        private readonly PaymentFactories $factories,
        private readonly PaymentMethodRepository $repository
    ) {
    }

    #[LiveProp(writable: true, url: true)]
    public string $method = '';

    #[PreMount]
    public function preMount(): void
    {
        $this->method = $this->method ?: $this->paymentMethods()[0]?->getGatewayName() ?? '';
    }

    /**
     * @return list<PaymentMethod|null>
     */
    #[ExposeInTemplate]
    public function paymentMethods(): array
    {
        return $this->repository->findBy([], ['name' => 'ASC']);
    }

    /**
     * @return array<string>
     */
    #[ExposeInTemplate]
    public function availablePaymentMethods(): array
    {
        $factories = $this->factories->getFactories();
        unset($factories['credit']);
        return array_keys($factories);
    }
}
