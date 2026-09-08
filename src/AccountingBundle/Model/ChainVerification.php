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

use Augias\AccountingBundle\Enum\LedgerBook;
use function count;

/**
 * The outcome of re-walking a book's hash chain.
 *
 * A verification that fails names the first entry where the chain stopped
 * matching and how, rather than reporting a bare "invalid": the point of
 * sealing the books is to be able to say precisely what was tampered with, and
 * from which entry onwards nothing can be trusted.
 */
final readonly class ChainVerification
{
    /** The recomputed hash differs from the stored one — the entry itself changed. */
    public const string REASON_ALTERED = 'altered';

    /** The entry's stored previous hash is not the hash of the entry before it. */
    public const string REASON_BROKEN_LINK = 'broken_link';

    /** Sequence numbers skip or repeat — an entry was removed or inserted. */
    public const string REASON_SEQUENCE_GAP = 'sequence_gap';

    /**
     * @param list<array{sequenceNumber: int|null, entryId: string|null, reason: string}> $failures
     *        in chain order; the first is where the book stopped being trustworthy
     */
    public function __construct(
        public LedgerBook $book,
        public int $entriesChecked,
        public array $failures = [],
    ) {
    }

    public function isValid(): bool
    {
        return [] === $this->failures;
    }

    public function failureCount(): int
    {
        return count($this->failures);
    }

    /**
     * @return array{sequenceNumber: int|null, entryId: string|null, reason: string}|null
     */
    public function firstFailure(): ?array
    {
        return $this->failures[0] ?? null;
    }
}
