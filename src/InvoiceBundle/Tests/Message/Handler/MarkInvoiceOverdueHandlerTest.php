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

namespace Augias\InvoiceBundle\Tests\Message\Handler;

use Augias\CoreBundle\Company\CompanySelector;
use Augias\CoreBundle\Test\Factory\CompanyFactory;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Message\Handler\MarkInvoiceOverdueHandler;
use Augias\InvoiceBundle\Message\MarkInvoiceOverdue;
use Augias\InvoiceBundle\Model\Graph;
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Augias\InvoiceBundle\Service\InvoiceStatusTransitionService;
use Augias\InvoiceBundle\Test\Factory\InvoiceFactory;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(MarkInvoiceOverdueHandler::class)]
final class MarkInvoiceOverdueHandlerTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use MockeryPHPUnitIntegration;

    public function testHandlerMarksInvoiceOverdue(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::yesterday(),
            'company' => $company,
        ]);

        $invoiceStateMachine = M::mock(WorkflowInterface::class);
        $registry = M::mock(ManagerRegistry::class);

        $invoiceStateMachine->shouldReceive('can')
            ->with(M::on(fn ($inv) => $inv->getId()->equals($invoice->getId())), Graph::TRANSITION_OVERDUE)
            ->once()
            ->andReturn(true);

        $invoiceStateMachine->shouldReceive('apply')
            ->with(M::on(fn ($inv) => $inv->getId()->equals($invoice->getId())), Graph::TRANSITION_OVERDUE)
            ->once();

        $em = M::mock(EntityManagerInterface::class);

        $registry->shouldReceive('getManager')
            ->once()
            ->andReturn($em);

        $em->shouldReceive('persist')
            ->with(M::on(fn ($inv) => $inv->getId()->equals($invoice->getId())))
            ->once();
        $em->shouldReceive('flush')
            ->once();

        $transitionService = new InvoiceStatusTransitionService(
            $invoiceStateMachine,
            $registry,
        );

        $companySelector = self::getContainer()->get(CompanySelector::class);
        $repository = self::getContainer()->get(InvoiceRepository::class);

        $handler = new MarkInvoiceOverdueHandler(
            $repository,
            $transitionService,
            $companySelector,
            new NullLogger()
        );

        $message = new MarkInvoiceOverdue($invoice->getId(), $company->getId());
        $handler($message);
    }

    public function testHandlerSkipsNonPendingInvoice(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Paid,
            'due' => CarbonImmutable::yesterday(),
            'company' => $company,
        ]);

        $transitionService = new InvoiceStatusTransitionService(
            M::mock(WorkflowInterface::class),
            M::mock(ManagerRegistry::class),
        );

        $companySelector = self::getContainer()->get(CompanySelector::class);
        $repository = self::getContainer()->get(InvoiceRepository::class);

        $handler = new MarkInvoiceOverdueHandler(
            $repository,
            $transitionService,
            $companySelector,
            $logger = new BufferingLogger()
        );

        $message = new MarkInvoiceOverdue($invoice->getId(), $company->getId());
        $handler($message);

        self::assertSame([
            [
                'info',
                'Invoice no longer pending, skipping overdue processing',
                [
                    'invoice_id' => $invoice->getId()->toString(),
                    'current_status' => 'paid',
                ],
            ],
        ], $logger->cleanLogs());
    }

    public function testHandlerLogsWarningWhenInvoiceNotFound(): void
    {
        $company = CompanyFactory::createOne();
        $nonExistentId = new Ulid();

        $transitionService = new InvoiceStatusTransitionService(
            M::mock(WorkflowInterface::class),
            M::mock(ManagerRegistry::class),
        );

        $logger = M::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')
            ->once()
            ->with('Invoice not found for overdue processing', M::any());

        $companySelector = self::getContainer()->get(CompanySelector::class);
        $repository = self::getContainer()->get(InvoiceRepository::class);

        $handler = new MarkInvoiceOverdueHandler(
            $repository,
            $transitionService,
            $companySelector,
            $logger
        );

        $message = new MarkInvoiceOverdue($nonExistentId, $company->getId());
        $handler($message);
    }

    public function testHandlerLogsErrorOnInvalidTransition(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::yesterday(),
            'company' => $company,
        ]);

        $invoiceStateMachine = M::mock(WorkflowInterface::class);
        $transitionService = new InvoiceStatusTransitionService(
            $invoiceStateMachine,
            M::mock(ManagerRegistry::class),
        );

        $invoiceStateMachine->shouldReceive('can')
            ->with(M::on(fn ($inv) => $inv->getId()->equals($invoice->getId())), Graph::TRANSITION_OVERDUE)
            ->once()
            ->andReturn(false);

        $logger = M::mock(LoggerInterface::class);
        $logger->shouldReceive('error')
            ->once()
            ->with('Invalid transition when marking invoice overdue', M::any());

        $companySelector = self::getContainer()->get(CompanySelector::class);
        $repository = self::getContainer()->get(InvoiceRepository::class);

        $handler = new MarkInvoiceOverdueHandler(
            $repository,
            $transitionService,
            $companySelector,
            $logger
        );

        $message = new MarkInvoiceOverdue($invoice->getId(), $company->getId());
        $handler($message);
    }
}
