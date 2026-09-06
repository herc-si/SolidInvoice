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

namespace Augias\CatalogBundle\Entity;

use Augias\CatalogBundle\Repository\ProductCategoryRepository;
use Augias\CoreBundle\Traits\Entity\CompanyAware;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Groups catalogue entries (e.g. "Prestations", "Matériel"). Mirrors
 * {@see \Augias\BillBundle\Entity\BillCategory} on the purchase side.
 *
 * @see \Augias\CatalogBundle\Tests\Entity\ProductCategoryTest
 */
#[ORM\Table(name: ProductCategory::TABLE_NAME)]
#[ORM\UniqueConstraint(columns: ['name', 'company_id'])]
#[ORM\Entity(repositoryClass: ProductCategoryRepository::class)]
class ProductCategory implements Stringable
{
    final public const string TABLE_NAME = 'product_categories';

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
