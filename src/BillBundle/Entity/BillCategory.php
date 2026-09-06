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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\BillBundle\Repository\BillCategoryRepository;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A user-managed expense category (e.g. "Supplies", "Rent"), for grouping
 * {@see Bill} records by spend type — the structured data a future
 * accounting/FEC export would read from. Deliberately simple, mirroring
 * {@see \SolidInvoice\TaxBundle\Entity\Tax}'s flat-list-of-records shape.
 *
 * @see \SolidInvoice\BillBundle\Tests\Entity\BillCategoryTest
 */
#[ORM\Table(name: BillCategory::TABLE_NAME)]
#[ORM\UniqueConstraint(columns: ['name', 'company_id'])]
#[ORM\Entity(repositoryClass: BillCategoryRepository::class)]
class BillCategory implements Stringable
{
    final public const string TABLE_NAME = 'bill_categories';

    use CompanyAware;

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 125)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 125)]
    private ?string $name = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
