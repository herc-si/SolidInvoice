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

namespace Augias\PaymentBundle\Tests\Listener\Doctrine;

use Augias\PaymentBundle\Entity\Payment;
use Augias\PaymentBundle\Entity\SecurityToken;
use Augias\PaymentBundle\Listener\Doctrine\PaymentSecurityTokenRemover;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentSecurityTokenRemover::class)]
final class PaymentSecurityTokenRemoverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testPreRemoveDeletesAssociatedTokens(): void
    {
        $payment = new Payment();
        $token1 = new SecurityToken();
        $token2 = new SecurityToken();

        $em = Mockery::mock(EntityManagerInterface::class);
        $repo = Mockery::mock(EntityRepository::class);
        $em->shouldReceive('getRepository')->with(SecurityToken::class)->andReturn($repo);
        $repo->shouldReceive('findBy')->once()->with(['payment' => $payment])->andReturn([$token1, $token2]);
        $em->shouldReceive('remove')->once()->with($token1);
        $em->shouldReceive('remove')->once()->with($token2);

        $args = new PreRemoveEventArgs($payment, $em);

        $listener = new PaymentSecurityTokenRemover();
        $listener->preRemove($payment, $args);
    }

    public function testPreRemoveDoesNothingWhenNoTokensFound(): void
    {
        $payment = new Payment();

        $em = Mockery::mock(EntityManagerInterface::class);
        $repo = Mockery::mock(EntityRepository::class);
        $em->shouldReceive('getRepository')->with(SecurityToken::class)->andReturn($repo);
        $repo->shouldReceive('findBy')->once()->with(['payment' => $payment])->andReturn([]);
        $em->shouldNotReceive('remove');

        $args = new PreRemoveEventArgs($payment, $em);

        $listener = new PaymentSecurityTokenRemover();
        $listener->preRemove($payment, $args);
    }
}
