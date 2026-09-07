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

use Augias\AccountingBundle\Repository\ThresholdAlertRepository;
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
 * A record that a turnover threshold was crossed and the user told about it.
 *
 * Its only reason to exist is to keep the daily threshold check from saying
 * the same thing every morning for the rest of the year: the unique constraint
 * on company, threshold and step means each milestone is raised exactly once
 * per year. Crossing back below a threshold does not clear it — the crossing
 * happened, and the year's cumulative turnover cannot go back down.
 *
 * @see \Augias\AccountingBundle\Tests\Entity\ThresholdAlertTest
 */
#[ORM\Table(name: ThresholdAlert::TABLE_NAME)]
#[ORM\UniqueConstraint(name: 'unique_threshold_alert', columns: ['company_id', 'threshold_key', 'period_year', 'step'])]
#[ORM\Entity(repositoryClass: ThresholdAlertRepository::class)]
class ThresholdAlert
{
    final public const string TABLE_NAME = 'accounting_threshold_alerts';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    /**
     * Which threshold, as
     * {@see \Augias\AccountingBundle\Model\Threshold::$key} names it — e.g.
     * `micro_ceiling.services_bic` or `vat_franchise.base.sale_of_goods`.
     */
    #[ORM\Column(name: 'threshold_key', type: Types::STRING, length: 100)]
    private string $thresholdKey;

    #[ORM\Column(name: 'period_year', type: Types::SMALLINT)]
    private int $year;

    /**
     * The milestone reached, as a percentage of the threshold (80, 100, …).
     * Part of the uniqueness key so approaching a limit and actually crossing
     * it are two separate, separately-notified events.
     */
    #[ORM\Column(name: 'step', type: Types::SMALLINT)]
    private int $step;

    /** Year-to-date turnover when the alert fired. Minor units. */
    #[ORM\Column(name: 'amount', type: BigIntegerType::NAME)]
    private BigNumber $amount;

    /** The threshold itself, frozen — it may be revised in a later release. */
    #[ORM\Column(name: 'threshold_amount', type: BigIntegerType::NAME)]
    private BigNumber $thresholdAmount;

    #[ORM\Column(name: 'currency_code', type: Types::STRING, length: 3)]
    private string $currencyCode;

    #[ORM\Column(name: 'triggered_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $triggeredAt;

    /**
     * Null when the notification could not be sent — the alert still counts as
     * raised, so it is never retried into a duplicate, but the gap is visible.
     */
    #[ORM\Column(name: 'notified_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $notifiedAt = null;

    public function __construct()
    {
        $this->amount = BigInteger::zero();
        $this->thresholdAmount = BigInteger::zero();
        $this->triggeredAt = new DateTimeImmutable();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getThresholdKey(): string
    {
        return $this->thresholdKey;
    }

    public function setThresholdKey(string $thresholdKey): self
    {
        $this->thresholdKey = $thresholdKey;

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

    public function getStep(): int
    {
        return $this->step;
    }

    public function setStep(int $step): self
    {
        $this->step = $step;

        return $this;
    }

    public function getAmount(): BigNumber
    {
        return $this->amount;
    }

    public function setAmount(BigNumber $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getThresholdAmount(): BigNumber
    {
        return $this->thresholdAmount;
    }

    public function setThresholdAmount(BigNumber $thresholdAmount): self
    {
        $this->thresholdAmount = $thresholdAmount;

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

    public function getMoney(): Money
    {
        return new Money((string) $this->amount, new Currency($this->currencyCode));
    }

    public function getThresholdMoney(): Money
    {
        return new Money((string) $this->thresholdAmount, new Currency($this->currencyCode));
    }

    public function getTriggeredAt(): DateTimeImmutable
    {
        return $this->triggeredAt;
    }

    public function setTriggeredAt(DateTimeImmutable $triggeredAt): self
    {
        $this->triggeredAt = $triggeredAt;

        return $this;
    }

    public function getNotifiedAt(): ?DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(?DateTimeImmutable $notifiedAt): self
    {
        $this->notifiedAt = $notifiedAt;

        return $this;
    }
}
