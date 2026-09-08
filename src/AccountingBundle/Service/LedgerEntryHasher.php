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
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\SettlementMethod;
use function hash;
use function implode;

/**
 * Computes the hash that chains one sealed ledger entry to the one before it.
 *
 * Sealing is what makes the books worth anything as evidence: change a figure
 * in a closed period and every hash after it stops matching, which
 * {@see LedgerChainVerifier} can demonstrate at any time.
 *
 * The canonical form below is the contract. It is deliberately explicit rather
 * than derived from the entity's properties by reflection: adding a column must
 * not silently change what a hash covers, or every book already sealed would
 * fail to verify after an upgrade. A field that has to enter the hash later
 * gets appended, and the change is then a visible one.
 */
final readonly class LedgerEntryHasher
{
    private const string SEPARATOR = '|';

    /**
     * The hash of an entry, given the hash of the entry before it in its book.
     * The first entry of a book chains onto an empty string.
     */
    public function hash(LedgerEntry $entry, ?string $previousHash): string
    {
        return hash('sha256', $this->canonicalForm($entry, $previousHash));
    }

    /**
     * Everything the hash commits to, in a fixed order.
     *
     * The sequence number is in it, so entries cannot be reordered within a
     * book; the previous hash is in it, so nothing can be inserted between two
     * of them.
     */
    public function canonicalForm(LedgerEntry $entry, ?string $previousHash): string
    {
        $nature = $entry->getActivityNature();
        $settlement = $entry->getSettlementMethod();

        return implode(self::SEPARATOR, [
            $previousHash ?? '',
            (string) $entry->getSequenceNumber(),
            $entry->getBook()->value,
            $entry->getEntryDate()->format('Y-m-d'),
            $entry->getLabel(),
            $entry->getCounterpartyName(),
            $entry->getDocumentReference() ?? '',
            (string) $entry->getAmount(),
            $entry->getCurrencyCode(),
            $nature instanceof ActivityNature ? $nature->value : '',
            $settlement instanceof SettlementMethod ? $settlement->value : '',
            $entry->getSource()->value,
            $entry->getSourceId()?->toRfc4122() ?? '',
        ]);
    }
}
