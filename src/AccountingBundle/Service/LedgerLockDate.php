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

use Augias\AccountingBundle\AccountingSettings;
use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Repository\AccountingPeriodRepository;
use Augias\CoreBundle\Entity\Company;
use Augias\SettingsBundle\SystemConfig;
use DateTimeImmutable;
use Throwable;
use function array_key_exists;
use function trim;

/**
 * The date the books are shut up to.
 *
 * Sealing a period is one-way and only happens once a period has ended, which
 * leaves a gap the user has no control over: the weeks between a quarter ending
 * and anyone getting round to closing it, during which figures that have often
 * already been declared are still editable. This is the cran in between —
 * reversible, set by hand, and the ordinary answer to "I have filed that month,
 * leave it alone".
 *
 * Two things decide it, and the later one wins:
 *
 * - the date the user set, which they can move in either direction;
 * - the end of the last period that was sealed, which they cannot go back on.
 *
 * Deriving the second rather than writing it into the setting is deliberate:
 * there is then no moment where the two disagree, and no migration needed the
 * day the rule changes. A setting dated before what is already sealed simply
 * has no effect.
 *
 * @see \Augias\AccountingBundle\Tests\Service\LedgerLockDateTest
 */
final class LedgerLockDate
{
    /** @var array<string, DateTimeImmutable|null> */
    private array $resolved = [];

    public function __construct(
        private readonly SystemConfig $systemConfig,
        private readonly AccountingProfileProvider $profileProvider,
        private readonly AccountingPeriodRepository $periodRepository,
    ) {
    }

    /**
     * Null when nothing is locked: no date set and nothing sealed yet.
     */
    public function forCompany(Company $company): ?DateTimeImmutable
    {
        $key = (string) $company->getId();

        // Read on every entry that is written or changed, so the answer is held
        // for the request rather than asked of the database each time.
        if (! array_key_exists($key, $this->resolved)) {
            $this->resolved[$key] = $this->resolve($company);
        }

        return $this->resolved[$key];
    }

    /**
     * Whether an entry is beyond change because the books it sits in are shut.
     *
     * The entry's period is what counts, not the entry's own date. Money that
     * turns up late keeps the date it moved on but is filed into the period
     * that is still open — correcting a typo on it has to stay possible, and
     * reading the entry date would wrongly refuse it.
     */
    public function shuts(LedgerEntry $entry): bool
    {
        $period = $entry->getPeriod();

        // An entry with no period has not been filed anywhere yet, so there is
        // nothing for a lock date to shut.
        if (null === $period) {
            return false;
        }

        $lockDate = $this->forCompany($entry->getCompany());

        return $lockDate instanceof DateTimeImmutable && $period->getEndDate() <= $lockDate;
    }

    /**
     * Forgets what was worked out, for the span of a process that closes a
     * period and then goes on writing — a test, or a console command.
     */
    public function reset(): void
    {
        $this->resolved = [];
    }

    private function resolve(Company $company): ?DateTimeImmutable
    {
        $sealed = $this->periodRepository->latestClosedPeriodEnd(
            $company,
            $this->profileProvider->forCompany($company)->declarationPeriodicity,
        );

        $configured = $this->configured($company);

        if (! $configured instanceof DateTimeImmutable) {
            return $sealed;
        }

        if (! $sealed instanceof DateTimeImmutable) {
            return $configured;
        }

        return $configured > $sealed ? $configured : $sealed;
    }

    private function configured(Company $company): ?DateTimeImmutable
    {
        $value = trim((string) $this->systemConfig->get(AccountingSettings::LOCK_DATE, $company));

        if ('' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value)->setTime(0, 0);
        } catch (Throwable) {
            // A hand-edited or half-migrated value. Behaving as if nothing were
            // locked would quietly unlock the books, so the sealed floor is
            // what is left to fall back on — resolve() handles that.
            return null;
        }
    }
}
