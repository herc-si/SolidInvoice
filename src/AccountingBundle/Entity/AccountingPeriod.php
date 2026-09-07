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

namespace Augias\AccountingBundle\Entity;

use Augias\AccountingBundle\Enum\PeriodStatus;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Repository\AccountingPeriodRepository;
use Augias\CoreBundle\Traits\Entity\CompanyAware;
use Augias\CoreBundle\Traits\Entity\TimeStampable;
use Augias\UserBundle\Entity\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * A month, quarter or year of bookkeeping, and the unit at which the books are
 * sealed. Periods are created lazily — the first entry dated inside one brings
 * it into existence — so an untouched company has none at all.
 *
 * Closing is the whole point of this entity: while {@see PeriodStatus::Open}
 * its entries can be corrected freely, and once {@see PeriodStatus::Closed}
 * they are numbered, hash-chained and frozen. The totals computed at that
 * moment are stored on {@see $totals} rather than recomputed on read, so a
 * later correction elsewhere — or a change to how totals are derived — can
 * never quietly rewrite what was closed.
 *
 * @see \Augias\AccountingBundle\Tests\Entity\AccountingPeriodTest
 */
#[ORM\Table(name: AccountingPeriod::TABLE_NAME)]
#[ORM\UniqueConstraint(name: 'unique_period_company', columns: ['company_id', 'period_type', 'period_year', 'period_ordinal'])]
#[ORM\Entity(repositoryClass: AccountingPeriodRepository::class)]
class AccountingPeriod implements Stringable
{
    final public const string TABLE_NAME = 'accounting_periods';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'period_type', type: Types::STRING, length: 10, enumType: PeriodType::class)]
    private PeriodType $type;

    #[ORM\Column(name: 'period_year', type: Types::SMALLINT)]
    private int $year;

    /**
     * 1-12 for a month, 1-4 for a quarter, always 1 for a year. Redundant with
     * {@see $startDate}, but it is what the unique constraint and every "the
     * period before this one" query are expressed in terms of.
     */
    #[ORM\Column(name: 'period_ordinal', type: Types::SMALLINT)]
    private int $ordinal;

    #[ORM\Column(name: 'start_date', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $startDate;

    /**
     * Inclusive — entries carry a date, not a timestamp.
     */
    #[ORM\Column(name: 'end_date', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $endDate;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 10, enumType: PeriodStatus::class)]
    private PeriodStatus $status = PeriodStatus::Open;

    #[ORM\Column(name: 'closed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    /**
     * Who closed it. Nullable and SET NULL on delete: the closure is a fact
     * about the books that must outlive the user account that performed it.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'closed_by_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $closedBy = null;

    /**
     * The hash of the last entry in the chain at the moment of closing — the
     * single value to compare against when re-verifying the period later.
     */
    #[ORM\Column(name: 'closing_hash', type: Types::STRING, length: 64, nullable: true)]
    private ?string $closingHash = null;

    #[ORM\Column(name: 'entry_count', type: Types::INTEGER, options: ['default' => 0])]
    private int $entryCount = 0;

    /**
     * Frozen figures, shaped as
     * `['currency' => 'EUR', 'revenue' => ['sale_of_goods' => '120000', ...], 'purchase' => '4500']`
     * with amounts as minor-unit strings. Deliberately a snapshot: see the
     * class docblock.
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'totals', type: Types::JSON, nullable: true)]
    private ?array $totals = null;

    /**
     * @var Collection<int, LedgerEntry>
     */
    #[ORM\OneToMany(targetEntity: LedgerEntry::class, mappedBy: 'period')]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getType(): PeriodType
    {
        return $this->type;
    }

    public function setType(PeriodType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getOrdinal(): int
    {
        return $this->ordinal;
    }

    public function setOrdinal(int $ordinal): self
    {
        $this->ordinal = $ordinal;

        return $this;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStatus(): PeriodStatus
    {
        return $this->status;
    }

    public function setStatus(PeriodStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isClosed(): bool
    {
        return $this->status === PeriodStatus::Closed;
    }

    public function isOpen(): bool
    {
        return $this->status === PeriodStatus::Open;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTimeImmutable $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getClosedBy(): ?User
    {
        return $this->closedBy;
    }

    public function setClosedBy(?User $closedBy): self
    {
        $this->closedBy = $closedBy;

        return $this;
    }

    public function getClosingHash(): ?string
    {
        return $this->closingHash;
    }

    public function setClosingHash(?string $closingHash): self
    {
        $this->closingHash = $closingHash;

        return $this;
    }

    public function getEntryCount(): int
    {
        return $this->entryCount;
    }

    public function setEntryCount(int $entryCount): self
    {
        $this->entryCount = $entryCount;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTotals(): ?array
    {
        return $this->totals;
    }

    /**
     * @param array<string, mixed>|null $totals
     */
    public function setTotals(?array $totals): self
    {
        $this->totals = $totals;

        return $this;
    }

    /**
     * @return Collection<int, LedgerEntry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function contains(DateTimeImmutable $date): bool
    {
        $date = $date->setTime(0, 0);

        return $date >= $this->startDate && $date <= $this->endDate;
    }

    /**
     * "2026-Q1" and friends — stable across locales, unlike a month name.
     */
    public function getLabel(): string
    {
        return $this->type->formatLabel($this->year, $this->ordinal);
    }

    public function __toString(): string
    {
        return $this->getLabel();
    }
}
