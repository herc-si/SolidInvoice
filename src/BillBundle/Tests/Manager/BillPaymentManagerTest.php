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

namespace Augias\BillBundle\Tests\Manager;

use Augias\BillBundle\Entity\Bill;
use Augias\BillBundle\Enum\BillPaymentMethod;
use Augias\BillBundle\Manager\BillPaymentManager;
use Augias\BillBundle\Model\Graph;
use Augias\CoreBundle\Entity\Company;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(BillPaymentManager::class)]
final class BillPaymentManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRecordPaymentPersistsAndAttachesThePaymentToTheBill(): void
    {
        $bill = new Bill();
        $bill->setCompany(new Company());
        $bill->setTotalAmount(BigInteger::of(10000))->setCurrencyCode('EUR');

        $workflow = M::mock(WorkflowInterface::class);
        $workflow->shouldReceive('can')->with($bill, Graph::TRANSITION_PAY)->andReturnFalse();

        $entityManager = M::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('persist')->once();
        $entityManager->shouldReceive('flush')->once();

        $manager = new BillPaymentManager($entityManager, $workflow);

        $payment = $manager->recordPayment(
            $bill,
            BigInteger::of(4000),
            new DateTimeImmutable('2026-01-15'),
            BillPaymentMethod::BankTransfer,
            'VIR-001',
            'Partial payment',
        );

        self::assertSame($bill, $payment->getBill());
        self::assertSame('4000', (string) $payment->getAmount());
        self::assertSame('EUR', $payment->getCurrencyCode());
        self::assertSame('VIR-001', $payment->getReference());
        self::assertSame('Partial payment', $payment->getNotes());
        self::assertTrue($bill->getPayments()->contains($payment));
    }

    public function testRecordPaymentAppliesThePayTransitionWhenTheBalanceReachesZero(): void
    {
        $bill = new Bill();
        $bill->setCompany(new Company());
        $bill->setTotalAmount(BigInteger::of(10000))->setCurrencyCode('EUR');

        $workflow = M::mock(WorkflowInterface::class);
        $workflow->shouldReceive('can')->with($bill, Graph::TRANSITION_PAY)->andReturnTrue();
        $workflow->shouldReceive('apply')->once()->with($bill, Graph::TRANSITION_PAY);

        $entityManager = M::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('persist')->once();
        $entityManager->shouldReceive('flush')->once();

        $manager = new BillPaymentManager($entityManager, $workflow);

        $manager->recordPayment(
            $bill,
            BigInteger::of(10000),
            new DateTimeImmutable('2026-01-15'),
            BillPaymentMethod::BankTransfer,
        );
    }

    public function testRecordPaymentDoesNotApplyTheTransitionWhenABalanceRemains(): void
    {
        $bill = new Bill();
        $bill->setCompany(new Company());
        $bill->setTotalAmount(BigInteger::of(10000))->setCurrencyCode('EUR');

        $workflow = M::mock(WorkflowInterface::class);
        $workflow->shouldNotReceive('apply');

        $entityManager = M::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('persist')->once();
        $entityManager->shouldReceive('flush')->once();

        $manager = new BillPaymentManager($entityManager, $workflow);

        $manager->recordPayment(
            $bill,
            BigInteger::of(4000),
            new DateTimeImmutable('2026-01-15'),
            BillPaymentMethod::BankTransfer,
        );
    }

    public function testRecordPaymentDoesNotApplyTheTransitionWhenTheWorkflowRefusesIt(): void
    {
        $bill = new Bill();
        $bill->setCompany(new Company());
        $bill->setTotalAmount(BigInteger::of(10000))->setCurrencyCode('EUR');
        // Already archived/cancelled, say — the workflow itself decides `can()` is false
        // even though the balance is now zero.

        $workflow = M::mock(WorkflowInterface::class);
        $workflow->shouldReceive('can')->with($bill, Graph::TRANSITION_PAY)->andReturnFalse();
        $workflow->shouldNotReceive('apply');

        $entityManager = M::mock(EntityManagerInterface::class);
        $entityManager->shouldReceive('persist')->once();
        $entityManager->shouldReceive('flush')->once();

        $manager = new BillPaymentManager($entityManager, $workflow);

        $manager->recordPayment(
            $bill,
            BigInteger::of(10000),
            new DateTimeImmutable('2026-01-15'),
            BillPaymentMethod::BankTransfer,
        );
    }
}
