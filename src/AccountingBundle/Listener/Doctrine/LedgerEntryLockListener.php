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

namespace Augias\AccountingBundle\Listener\Doctrine;

use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Exception\LedgerLockedException;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Refuses any change to a sealed ledger entry, wherever it comes from.
 *
 * The forms and the voters already keep a locked entry out of the user's
 * reach, but a guarantee that lives only in the UI is not a guarantee: an API
 * client, a console command or a future feature would each have to remember it.
 * Enforcing it on the way to the database is what actually makes "closing is
 * final" true.
 *
 * The one exception is the sealing itself, which sets the numbers, hashes and
 * lock on entries that were open when the update began — {@see $lockedAt} was
 * null before, so it does not match a locked entry being changed.
 *
 * @see \Augias\AccountingBundle\Tests\Functional\LedgerBookkeepingTest
 */
#[AsEntityListener(event: Events::preUpdate, entity: LedgerEntry::class)]
#[AsEntityListener(event: Events::preRemove, entity: LedgerEntry::class)]
final class LedgerEntryLockListener
{
    public function preUpdate(LedgerEntry $entry, PreUpdateEventArgs $args): void
    {
        // The pre-update value is what matters: an entry being sealed right now
        // reads as locked already, since the new value is set by then.
        if (! $this->wasLockedBeforeThisChange($args)) {
            return;
        }

        throw LedgerLockedException::forEntry($entry);
    }

    public function preRemove(LedgerEntry $entry, PreRemoveEventArgs $args): void
    {
        if (! $entry->isLocked()) {
            return;
        }

        throw LedgerLockedException::forEntry($entry);
    }

    /**
     * Whether the entry was already sealed when this update began.
     *
     * Read from the lock's own previous value rather than from the set of
     * fields being written: sealing writes the numbers, the hashes and the
     * lock, and a timestampable `updated` alongside them, so a list of
     * permitted fields would have to be kept in step with every listener that
     * ever touches the entity. What actually matters is simpler — an entry that
     * had no lock a moment ago is being sealed, and one that had is being
     * tampered with. Clearing a lock is a change to a sealed entry too, and is
     * refused by the same reading.
     */
    private function wasLockedBeforeThisChange(PreUpdateEventArgs $args): bool
    {
        if ($args->hasChangedField('lockedAt')) {
            return null !== $args->getOldValue('lockedAt');
        }

        $entity = $args->getObject();

        return $entity instanceof LedgerEntry && $entity->isLocked();
    }
}
