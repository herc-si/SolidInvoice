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

use Augias\AccountingBundle\Enum\DeclarationStatus;
use Augias\AccountingBundle\Repository\DeclarationRepository;
use Augias\CoreBundle\Doctrine\Type\BigIntegerType;
use Augias\CoreBundle\Traits\Entity\CompanyAware;
use Augias\CoreBundle\Traits\Entity\TimeStampable;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * The turnover declaration for one {@see AccountingPeriod} — what the user has
 * to report to the collecting body, and what it will cost them.
 *
 * Augias files nothing. It computes the figures, the user copies them onto the
 * authority's own site, and comes back to record that they did along with the
 * reference they were given ({@see $reference}).
 *
 * The computed lines are stored on {@see $lines} rather than recomputed on
 * read, and {@see $rateVersion} records which vintage of the rate table
 * produced them. Contribution rates change every year and are routinely
 * revised: without freezing, opening a two-year-old declaration would silently
 * show figures that were never declared.
 *
 * @see \Augias\AccountingBundle\Tests\Entity\DeclarationTest
 */
#[ORM\Table(name: Declaration::TABLE_NAME)]
#[ORM\UniqueConstraint(name: 'unique_declaration_period', columns: ['period_id'])]
#[ORM\Entity(repositoryClass: DeclarationRepository::class)]
class Declaration
{
    final public const string TABLE_NAME = 'accounting_declarations';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: AccountingPeriod::class)]
    #[ORM\JoinColumn(name: 'period_id', nullable: false, onDelete: 'CASCADE')]
    private AccountingPeriod $period;

    /**
     * Which regime computed this, kept as a plain string so a declaration
     * survives the regime being renamed or removed from the registry.
     */
    #[ORM\Column(name: 'regime_code', type: Types::STRING, length: 50)]
    private string $regimeCode;

    /**
     * The dated rate set used, e.g. "2026-01-01". Makes it possible to explain
     * an old declaration's numbers years later.
     */
    #[ORM\Column(name: 'rate_version', type: Types::STRING, length: 20, nullable: true)]
    private ?string $rateVersion = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 20, enumType: DeclarationStatus::class)]
    private DeclarationStatus $status = DeclarationStatus::Draft;

    #[ORM\Column(name: 'currency_code', type: Types::STRING, length: 3)]
    private string $currencyCode;

    /** Turnover to report, all activity natures together. Minor units. */
    #[ORM\Column(name: 'total_turnover', type: BigIntegerType::NAME)]
    private BigNumber $totalTurnover;

    /** Social contributions and any training levy. Minor units. */
    #[ORM\Column(name: 'total_contributions', type: BigIntegerType::NAME)]
    private BigNumber $totalContributions;

    /** Everything payable, contributions plus the optional flat income-tax payment. */
    #[ORM\Column(name: 'total_due', type: BigIntegerType::NAME)]
    private BigNumber $totalDue;

    /**
     * The itemised computation, one element per
     * {@see \Augias\AccountingBundle\Model\DeclarationLine} — base, rate,
     * amount and the label each was derived from. Frozen; see the class
     * docblock.
     *
     * @var array<int, array<string, mixed>>
     */
    #[ORM\Column(name: 'lines', type: Types::JSON)]
    private array $lines = [];

    #[ORM\Column(name: 'submitted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $submittedAt = null;

    /**
     * The filing reference the authority handed back, typed in by the user.
     */
    #[ORM\Column(name: 'reference', type: Types::STRING, length: 125, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->totalTurnover = BigInteger::zero();
        $this->totalContributions = BigInteger::zero();
        $this->totalDue = BigInteger::zero();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getPeriod(): AccountingPeriod
    {
        return $this->period;
    }

    public function setPeriod(AccountingPeriod $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getRegimeCode(): string
    {
        return $this->regimeCode;
    }

    public function setRegimeCode(string $regimeCode): self
    {
        $this->regimeCode = $regimeCode;

        return $this;
    }

    public function getRateVersion(): ?string
    {
        return $this->rateVersion;
    }

    public function setRateVersion(?string $rateVersion): self
    {
        $this->rateVersion = $rateVersion;

        return $this;
    }

    public function getStatus(): DeclarationStatus
    {
        return $this->status;
    }

    public function setStatus(DeclarationStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getTotalTurnover(): BigNumber
    {
        return $this->totalTurnover;
    }

    public function setTotalTurnover(BigNumber $totalTurnover): self
    {
        $this->totalTurnover = $totalTurnover;

        return $this;
    }

    public function getTotalContributions(): BigNumber
    {
        return $this->totalContributions;
    }

    public function setTotalContributions(BigNumber $totalContributions): self
    {
        $this->totalContributions = $totalContributions;

        return $this;
    }

    public function getTotalDue(): BigNumber
    {
        return $this->totalDue;
    }

    public function setTotalDue(BigNumber $totalDue): self
    {
        $this->totalDue = $totalDue;

        return $this;
    }

    public function getTurnoverMoney(): Money
    {
        return new Money((string) $this->totalTurnover, new Currency($this->currencyCode));
    }

    public function getDueMoney(): Money
    {
        return new Money((string) $this->totalDue, new Currency($this->currencyCode));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     */
    public function setLines(array $lines): self
    {
        $this->lines = $lines;

        return $this;
    }

    public function getSubmittedAt(): ?DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?DateTimeImmutable $submittedAt): self
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function isSubmitted(): bool
    {
        return $this->status === DeclarationStatus::Submitted;
    }

    /**
     * A submitted declaration is a record of what was actually filed, so it is
     * never recomputed — regenerating would make it disagree with the return
     * the user sent.
     */
    public function isRecomputable(): bool
    {
        return ! $this->isSubmitted();
    }
}
