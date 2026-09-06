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

namespace Augias\InvoiceBundle\Tests\Service;

use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\CoreBundle\Test\Traits\DoctrineTestTrait;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Exception\InvalidTransitionException;
use Augias\InvoiceBundle\Model\Graph;
use Augias\InvoiceBundle\Service\InvoiceStatusTransitionService;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

#[CoversClass(InvoiceStatusTransitionService::class)]
final class InvoiceStatusTransitionServiceTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;

    public function testApplyTransition(): void
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne());
        $invoice->setStatus(InvoiceStatus::Pending);

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('can')
            ->once()
            ->with($invoice, Graph::TRANSITION_OVERDUE)
            ->andReturn(true);

        $stateMachine->shouldReceive('apply')
            ->once()
            ->with($invoice, Graph::TRANSITION_OVERDUE);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);
        $service->applyTransition($invoice, Graph::TRANSITION_OVERDUE);

        // Verify invoice was persisted
        self::assertSame($invoice, $this->em->getRepository(Invoice::class)->find($invoice->getId()));
    }

    public function testApplyTransitionThrowsExceptionWhenTransitionNotAllowed(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(InvoiceStatus::Paid);

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('can')
            ->once()
            ->with($invoice, Graph::TRANSITION_OVERDUE)
            ->andReturn(false);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);

        $this->expectException(InvalidTransitionException::class);
        $service->applyTransition($invoice, Graph::TRANSITION_OVERDUE);
    }

    public function testCanApplyTransition(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(InvoiceStatus::Pending);

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('can')
            ->once()
            ->with($invoice, Graph::TRANSITION_OVERDUE)
            ->andReturn(true);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);

        self::assertTrue($service->canApplyTransition($invoice, Graph::TRANSITION_OVERDUE));
    }

    public function testGetAvailableTransitions(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(InvoiceStatus::Pending);

        $transition1 = M::mock(Transition::class);
        $transition1->shouldReceive('getName')
            ->andReturn(Graph::TRANSITION_OVERDUE);

        $transition2 = M::mock(Transition::class);
        $transition2->shouldReceive('getName')
            ->andReturn(Graph::TRANSITION_PAY);

        $stateMachine = M::mock(StateMachine::class);
        $stateMachine->shouldReceive('getEnabledTransitions')
            ->once()
            ->with($invoice)
            ->andReturn([$transition1, $transition2]);

        $service = new InvoiceStatusTransitionService($stateMachine, $this->registry);
        $transitions = $service->getAvailableTransitions($invoice);

        self::assertSame([Graph::TRANSITION_OVERDUE, Graph::TRANSITION_PAY], $transitions);
    }
}
