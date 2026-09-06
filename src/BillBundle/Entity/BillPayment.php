<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\BillBundle\Entity;

use Brick\Math\BigNumber;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use SolidInvoice\BillBundle\Enum\BillPaymentMethod;
use SolidInvoice\BillBundle\Repository\BillPaymentRepository;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * Money paid out against a {@see Bill} — recorded manually, not captured via a
 * payment gateway, so this deliberately does not extend
 * {@see \Payum\Core\Model\Payment} the way {@see \SolidInvoice\PaymentBundle\Entity\Payment}
 * does: there's no gateway state, client email, or capture callback here,
 * just "we paid this much, this way, on this date".
 *
 * @see \SolidInvoice\BillBundle\Tests\Entity\BillPaymentTest
 */
#[ORM\Table(name: BillPayment::TABLE_NAME)]
#[ORM\Entity(repositoryClass: BillPaymentRepository::class)]
class BillPayment
{
    final public const string TABLE_NAME = 'bill_payments';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: Bill::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(name: 'bill_id', nullable: false, onDelete: 'CASCADE')]
    private Bill $bill;

    #[ORM\Column(name: 'amount', type: BigIntegerType::NAME)]
    private BigNumber $amount;

    #[ORM\Column(name: 'currency_code', type: Types::STRING, length: 3)]
    private string $currencyCode;

    #[ORM\Column(name: 'paid_date', type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $paidDate;

    #[ORM\Column(name: 'method', type: Types::STRING, length: 20, enumType: BillPaymentMethod::class)]
    private BillPaymentMethod $method;

    #[ORM\Column(name: 'reference', type: Types::STRING, length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getBill(): Bill
    {
        return $this->bill;
    }

    public function setBill(Bill $bill): self
    {
        $this->bill = $bill;

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

    public function getPaidDate(): DateTimeImmutable
    {
        return $this->paidDate;
    }

    public function setPaidDate(DateTimeImmutable $paidDate): self
    {
        $this->paidDate = $paidDate;

        return $this;
    }

    public function getMethod(): BillPaymentMethod
    {
        return $this->method;
    }

    public function setMethod(BillPaymentMethod $method): self
    {
        $this->method = $method;

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
}
