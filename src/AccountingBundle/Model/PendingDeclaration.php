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

namespace Augias\AccountingBundle\Model;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\Declaration;
use Augias\AccountingBundle\Enum\DeclarationAction;
use DateTimeImmutable;

/**
 * A period that has ended and has not been declared, and what is left to do
 * about it.
 *
 * The declaration may be null, and that is not the same as a missing row being
 * an error: {@see \Augias\AccountingBundle\Service\DeclarationBuilder} computes
 * one the first time somebody opens the period, so a quarter nobody has looked
 * at yet has none. Either way it is undeclared, which is all this reports.
 *
 * @see \Augias\AccountingBundle\Tests\Model\PendingDeclarationTest
 */
final readonly class PendingDeclaration
{
    public DeclarationAction $action;

    public function __construct(
        public AccountingPeriod $period,
        public ?Declaration $declaration = null,
    ) {
        // An open period has to be sealed before its figures mean anything, so
        // that is the step the user is on whatever the declaration says.
        $this->action = $period->isOpen() ? DeclarationAction::Close : DeclarationAction::File;
    }

    /**
     * Whole days since the period ended.
     *
     * Deliberately not "days until the deadline". Filing deadlines depend on
     * the collecting body, the periodicity and the year, and none of that is
     * modelled or verified here — inventing one would put a date on screen that
     * the user might rely on. How long the period has been sitting there is
     * something Augias actually knows.
     */
    public function daysSincePeriodEnded(?DateTimeImmutable $on = null): int
    {
        $on ??= new DateTimeImmutable('today');

        return (int) $this->period->getEndDate()->diff($on->setTime(0, 0))->format('%r%a');
    }
}
