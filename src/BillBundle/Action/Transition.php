<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\BillBundle\Action;

use Doctrine\Persistence\ManagerRegistry;
use Generator;
use SolidInvoice\BillBundle\Entity\Bill;
use SolidInvoice\BillBundle\Enum\BillStatus;
use SolidInvoice\BillBundle\Exception\InvalidTransitionException;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class Transition
{
    public function __construct(
        private RouterInterface $router,
        private WorkflowInterface $billStateMachine,
        private ManagerRegistry $doctrine,
    ) {
    }

    public function __invoke(Request $request, string $action, Bill $bill): RedirectResponse
    {
        if (! $this->billStateMachine->can($bill, $action)) {
            throw new InvalidTransitionException($action);
        }

        $marking = $this->billStateMachine->apply($bill, $action);

        $this->doctrine->getManager()->flush();

        $route = $this->router->generate('_bills_view', ['id' => $bill->getId()]);

        if ($marking->has(BillStatus::Archived->value)) {
            $route = $this->router->generate('_bills_index');
        }

        return new class($action, $route) extends RedirectResponse implements FlashResponse {
            public function __construct(
                private readonly string $action,
                string $route
            ) {
                parent::__construct($route);
            }

            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'bill.transition.action.' . $this->action;
            }
        };
    }
}
