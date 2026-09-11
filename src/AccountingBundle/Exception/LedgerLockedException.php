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

namespace Augias\AccountingBundle\Exception;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\LedgerEntry;
use RuntimeException;
use function sprintf;

/**
 * Raised on any attempt to change what has been sealed. This is the guarantee
 * the whole closing mechanism exists to provide, so it is an exception rather
 * than a silently-ignored write: a caller that reaches here has a bug, and
 * quietly dropping the change would leave the user thinking it took effect.
 */
final class LedgerLockedException extends RuntimeException
{
    public static function forEntry(LedgerEntry $entry): self
    {
        return new self(sprintf(
            'Ledger entry %s belongs to a closed period and can no longer be modified or removed. '
            . 'Record a reversing entry in an open period instead.',
            $entry->getId()?->toBase58() ?? '(unsaved)',
        ));
    }

    public static function beforeLockDate(LedgerEntry $entry): self
    {
        return new self(sprintf(
            'Ledger entry %s sits in a period the books have been shut on and can no longer be '
            . 'modified or removed. Move the lock date back, or record a reversing entry in an '
            . 'open period.',
            $entry->getId()?->toBase58() ?? '(unsaved)',
        ));
    }

    public static function forPeriod(AccountingPeriod $period): self
    {
        return new self(sprintf(
            'Accounting period %s is already closed.',
            $period->getLabel(),
        ));
    }

    public static function periodHasNotEnded(AccountingPeriod $period): self
    {
        return new self(sprintf(
            'Accounting period %s cannot be closed before it has ended on %s — '
            . 'money received before that date still belongs in it, and a sealed '
            . 'period cannot take it.',
            $period->getLabel(),
            $period->getEndDate()->format('Y-m-d'),
        ));
    }

    public static function earlierPeriodStillOpen(AccountingPeriod $period): self
    {
        return new self(sprintf(
            'Accounting period %s cannot be closed while an earlier period is still open — '
            . 'closing out of order would break the entry numbering and the hash chain.',
            $period->getLabel(),
        ));
    }
}
