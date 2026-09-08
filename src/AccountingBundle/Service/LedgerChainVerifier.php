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

namespace Augias\AccountingBundle\Service;

use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Model\ChainVerification;
use Augias\AccountingBundle\Repository\LedgerEntryRepository;
use Augias\CoreBundle\Entity\Company;
use function count;

/**
 * Re-walks a sealed book and reports whether it still hashes to what it was
 * sealed as.
 *
 * This is the payoff of the whole closing mechanism. Sealing on its own only
 * makes tampering *detectable*; something has to actually go and look, and be
 * able to say where the book stopped being trustworthy. A user asked to trust
 * their own accounts should be able to demand the check on demand — and, if it
 * ever fails, be told which entry and in what way rather than handed a red
 * cross.
 *
 * Only sealed entries take part: an open period is meant to be editable, so
 * hashing it would be checking a promise nobody made.
 *
 * @see \Augias\AccountingBundle\Tests\Service\LedgerChainVerifierTest
 */
final readonly class LedgerChainVerifier
{
    public function __construct(
        private LedgerEntryRepository $entryRepository,
        private LedgerEntryHasher $hasher,
    ) {
    }

    public function verify(Company $company, LedgerBook $book): ChainVerification
    {
        $entries = $this->entryRepository->findSealedInChainOrder($company, $book);

        $failures = [];
        $previousHash = null;
        $expectedNumber = null;

        foreach ($entries as $entry) {
            $number = $entry->getSequenceNumber();

            // Checked before the hashes: a gap says an entry was removed, and
            // reporting that as a broken link would name the wrong culprit.
            if (null !== $expectedNumber && $number !== $expectedNumber) {
                $failures[] = $this->failure($entry, ChainVerification::REASON_SEQUENCE_GAP);
            }

            if ($entry->getPreviousHash() !== $previousHash) {
                $failures[] = $this->failure($entry, ChainVerification::REASON_BROKEN_LINK);
            }

            if ($entry->getHash() !== $this->hasher->hash($entry, $entry->getPreviousHash())) {
                $failures[] = $this->failure($entry, ChainVerification::REASON_ALTERED);
            }

            $previousHash = $entry->getHash();
            $expectedNumber = null === $number ? null : $number + 1;
        }

        return new ChainVerification($book, count($entries), $failures);
    }

    /**
     * @return array{sequenceNumber: int|null, entryId: string|null, reason: string}
     */
    private function failure(LedgerEntry $entry, string $reason): array
    {
        return [
            'sequenceNumber' => $entry->getSequenceNumber(),
            'entryId' => $entry->getId()?->toBase58(),
            'reason' => $reason,
        ];
    }
}
