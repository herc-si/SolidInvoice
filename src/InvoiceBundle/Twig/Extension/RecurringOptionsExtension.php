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

namespace Augias\InvoiceBundle\Twig\Extension;

use Augias\CronBundle\Enum\ScheduleEndType;
use Augias\CronBundle\Enum\ScheduleRecurringType;
use Augias\InvoiceBundle\Entity\RecurringOptions;
use Augias\InvoiceBundle\Recurring\RecurringSchedule;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RecurringOptionsExtension extends AbstractExtension
{
    public function __construct(
        private readonly RecurringSchedule $schedule
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('recurring_frequency', $this->getRecurringFrequency(...)),
            new TwigFunction('recurring_occurrences', $this->schedule->getNextOccurrences(...)),
            new TwigFunction('recurring_end_date', $this->schedule->getEndDate(...)),
        ];
    }

    public function getRecurringFrequency(RecurringOptions $recurringOptions, bool $includeStartEndDates = true): string
    {
        $frequency = $this->schedule->getFrequency($recurringOptions);

        if ($frequency === '' || $frequency === '0') {
            return '';
        }

        $format = match ($recurringOptions->getType()) {
            ScheduleRecurringType::YEARLY => 'F Y',
            default => 'd F Y',
        };

        /*if (! isset($this->endType) || ! $this->recurringInvoice->getDateStart() instanceof DateTimeInterface) {
            return $frequency;
        }*/

        if (! $recurringOptions->hasEndType()) {
            return $frequency;
        }

        if (! $includeStartEndDates) {
            return $frequency;
        }

        return $frequency . match ($recurringOptions->getEndType()) {
            ScheduleEndType::ON => sprintf(' from %s to %s', $recurringOptions->getRecurringInvoice()->getDateStart()?->format($format), $this->schedule->getEndDate($recurringOptions)?->format($format)),
            ScheduleEndType::AFTER => sprintf(' from %s to %s (%d occurrences)', $recurringOptions->getRecurringInvoice()->getDateStart()?->format($format), $this->schedule->getEndDate($recurringOptions)?->format($format), $recurringOptions->getEndOccurrence()),
            ScheduleEndType::NEVER => sprintf(' from %s', $recurringOptions->getRecurringInvoice()->getDateStart()?->format($format)),
        };
    }
}
