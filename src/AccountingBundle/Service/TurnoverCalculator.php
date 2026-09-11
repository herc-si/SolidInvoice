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

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Model\TurnoverSummary;
use Augias\AccountingBundle\Repository\LedgerEntryRepository;
use Augias\CoreBundle\Entity\Company;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use function array_keys;

/**
 * Reads turnover off the revenue book — the one input every ceiling check and
 * every contribution calculation works from.
 *
 * It is the book that is summed, not the invoices: under a cash-basis regime
 * turnover is what was received, and the two differ by every unpaid invoice.
 * The split by activity nature comes from the entries themselves, since a
 * company may take money of more than one kind and the ceilings and rates
 * differ per kind.
 *
 * Entries booked in another currency are counted and named rather than
 * converted. An exchange rate invented here would end up inside a declaration,
 * and the books never recorded one.
 *
 * @see \Augias\AccountingBundle\Tests\Functional\ThresholdMonitorTest
 */
final readonly class TurnoverCalculator
{
    public function __construct(
        private LedgerEntryRepository $entryRepository,
    ) {
    }

    public function forRange(
        Company $company,
        string $currencyCode,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): TurnoverSummary {
        $rows = $this->entryRepository->sumByActivityNature($company, LedgerBook::Revenue, $from, $to);

        $byNature = [];
        $foreign = [];

        foreach ($rows as $row) {
            if ($row['currency'] !== $currencyCode) {
                $foreign[$row['currency']] = true;

                continue;
            }

            // Doctrine hydrates the enum column into an ActivityNature even in
            // an array result, while TurnoverSummary is keyed by the backing
            // value — so it is unwrapped here rather than at every read.
            //
            // Entries with no nature recorded — a hand-typed receipt where the
            // user left it blank — are summed under the empty key rather than
            // being dropped or guessed at. They count towards nothing per
            // activity, and the declaration screens show them for what they
            // are: turnover nobody has classified yet.
            $nature = $row['nature'] instanceof ActivityNature ? $row['nature']->value : (string) ($row['nature'] ?? '');
            $byNature[$nature] = ($byNature[$nature] ?? BigInteger::zero())->plus($row['total']);
        }

        return new TurnoverSummary(
            from: $from,
            to: $to,
            currencyCode: $currencyCode,
            byNature: $byNature,
            foreignCurrencies: array_keys($foreign),
        );
    }

    /**
     * Turnover for one closed or open period.
     */
    public function forPeriod(AccountingPeriod $period, string $currencyCode): TurnoverSummary
    {
        return $this->forRange(
            $period->getCompany(),
            $currencyCode,
            $period->getStartDate(),
            $period->getEndDate(),
        );
    }

    /**
     * Turnover since 1 January of the year the given date falls in.
     *
     * The ceilings and the VAT thresholds are annual and cumulative, so this —
     * not the period's own figure — is what they are measured against.
     */
    /**
     * Turnover since 1 January, whatever month the financial year opens on.
     *
     * Deliberately the calendar year and not the exercice: the ceilings this
     * feeds are a calendar-year measure in law, so a company that moved its
     * financial year would otherwise be told it was within a limit it had
     * crossed. The exercice decides what a yearly *period* covers; it does not
     * decide when a ceiling resets.
     */
    public function yearToDate(Company $company, string $currencyCode, DateTimeImmutable $on): TurnoverSummary
    {
        return $this->forRange(
            $company,
            $currencyCode,
            $on->setDate((int) $on->format('Y'), 1, 1)->setTime(0, 0),
            $on,
        );
    }
}
