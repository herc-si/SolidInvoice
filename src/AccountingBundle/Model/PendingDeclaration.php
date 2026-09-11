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
use LogicException;

/**
 * A period that has ended and has not been declared, and what is left to do
 * about it.
 *
 * It stands for one of two things, and never both: a period that exists, or a
 * gap in the calendar where one should. The second is the quarter in which
 * nothing was received — periods are created when an entry is filed into one,
 * so it has no row, and a nil return has nothing to attach itself to until
 * somebody creates it.
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

    private function __construct(
        public ?AccountingPeriod $period,
        public ?MissingPeriod $missing,
        public ?Declaration $declaration,
        DeclarationAction $action,
    ) {
        $this->action = $action;
    }

    public static function forPeriod(AccountingPeriod $period, ?Declaration $declaration = null): self
    {
        return new self(
            $period,
            null,
            $declaration,
            // An open period has to be sealed before its figures mean anything,
            // so that is the step the user is on whatever the declaration says.
            $period->isOpen() ? DeclarationAction::Close : DeclarationAction::File,
        );
    }

    /**
     * A period the calendar says should exist and that nothing brought into
     * being — a quarter in which no money was received.
     *
     * It is still outstanding: a regime may want a nil return for it, and the
     * user cannot file one against a period that does not exist. The step they
     * are on is creating it.
     */
    public static function forMissing(MissingPeriod $missing): self
    {
        return new self(null, $missing, null, DeclarationAction::Create);
    }

    /**
     * "2026-Q1" and friends, whichever of the two this stands for.
     */
    public function getLabel(): string
    {
        return $this->period instanceof AccountingPeriod
            ? $this->period->getLabel()
            : $this->requireMissing()->getLabel();
    }

    /**
     * Any date inside the period, for the form that creates it.
     */
    public function getStartDate(): DateTimeImmutable
    {
        return $this->period instanceof AccountingPeriod
            ? $this->period->getStartDate()
            : $this->requireMissing()->startDate;
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
        $endDate = $this->period instanceof AccountingPeriod
            ? $this->period->getEndDate()
            : $this->requireMissing()->endDate;

        return (int) $endDate->diff($on->setTime(0, 0))->format('%r%a');
    }

    /**
     * Exactly one of the two is always set — the constructor is private and both
     * factories set one. This is the assertion that says so to a reader and to
     * the analyser.
     */
    private function requireMissing(): MissingPeriod
    {
        if (! $this->missing instanceof MissingPeriod) {
            throw new LogicException('A pending declaration stands for either a period or a gap, and this one stands for neither.');
        }

        return $this->missing;
    }
}
