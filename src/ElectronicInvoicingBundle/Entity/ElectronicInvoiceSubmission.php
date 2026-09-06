<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\ElectronicInvoicingBundle\Entity;

use Augias\CoreBundle\Traits\Entity\CompanyAware;
use Augias\CoreBundle\Traits\Entity\TimeStampable;
use Augias\ElectronicInvoicingBundle\Repository\ElectronicInvoiceSubmissionRepository;
use Augias\InvoiceBundle\Entity\Invoice;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * One historical record of an invoice being sent to an electronic-invoicing
 * provider (success or failure) — the equivalent of a Payment attempt, but
 * for e-invoice transmission rather than money capture.
 *
 * @see \Augias\ElectronicInvoicingBundle\Tests\Entity\ElectronicInvoiceSubmissionTest
 */
#[ORM\Entity(repositoryClass: ElectronicInvoiceSubmissionRepository::class)]
#[ORM\Table(name: ElectronicInvoiceSubmission::TABLE_NAME)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'company', inversedBy: 'electronicInvoiceSubmissions')])]
class ElectronicInvoiceSubmission
{
    public const string TABLE_NAME = 'einvoicing_submission';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'electronicInvoiceSubmissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Invoice $invoice;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $provider;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $success;

    #[ORM\Column(name: 'external_reference', type: Types::STRING, length: 255, nullable: true)]
    private ?string $externalReference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    /**
     * The provider's latest known processing status for this submission (e.g.
     * SUPER PDP's `fr:205`/`fr:210` codes), refreshed by a polling command
     * since not every provider exposes webhooks. Null for providers that
     * don't report an asynchronous status, or before the first poll.
     */
    #[ORM\Column(name: 'status_code', type: Types::STRING, length: 64, nullable: true)]
    private ?string $statusCode = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }

    public function setExternalReference(?string $externalReference): self
    {
        $this->externalReference = $externalReference;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getStatusCode(): ?string
    {
        return $this->statusCode;
    }

    public function setStatusCode(?string $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }
}
