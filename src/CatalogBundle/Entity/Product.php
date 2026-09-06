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

namespace SolidInvoice\CatalogBundle\Entity;

use Brick\Math\BigInteger;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CatalogBundle\Enum\ProductType;
use SolidInvoice\CatalogBundle\Enum\ProductUnit;
use SolidInvoice\CatalogBundle\Repository\ProductRepository;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\TaxBundle\Entity\Tax;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A re-usable catalogue entry that pre-fills an invoice or quote line, so the
 * same service never has to be typed out twice.
 *
 * Prices are held in the company's own currency and copied onto the line as-is;
 * a client billed in another currency needs the amount adjusting by hand, which
 * is why nothing here is snapshot onto the document beyond what the line stores
 * for itself.
 *
 * @see \SolidInvoice\CatalogBundle\Tests\Entity\ProductTest
 */
#[ORM\Table(name: Product::TABLE_NAME)]
#[ORM\UniqueConstraint(columns: ['reference', 'company_id'])]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product implements Stringable
{
    final public const string TABLE_NAME = 'products';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 125)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 125)]
    private ?string $name = null;

    /**
     * Copied into the line's own description, which the user is then free to
     * edit per document without touching the catalogue.
     */
    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Optional internal reference (SKU). Unique per company when set, so an
     * import or a quick search can address an entry unambiguously.
     */
    #[ORM\Column(name: 'reference', type: Types::STRING, length: 64, nullable: true)]
    #[Assert\Length(max: 64)]
    private ?string $reference = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 16, enumType: ProductType::class)]
    private ProductType $type = ProductType::Service;

    #[ORM\Column(name: 'unit', type: Types::STRING, length: 16, enumType: ProductUnit::class)]
    private ProductUnit $unit = ProductUnit::Unit;

    #[ORM\Column(name: 'sale_price', type: BigIntegerType::NAME)]
    #[Assert\NotNull]
    private ?BigInteger $salePrice = null;

    /**
     * What the entry costs this company, used to show a margin. Null when it
     * isn't bought in — a service billed at a day rate usually has none.
     */
    #[ORM\Column(name: 'purchase_price', type: BigIntegerType::NAME, nullable: true)]
    private ?BigInteger $purchasePrice = null;

    #[ORM\ManyToOne(targetEntity: Tax::class)]
    #[ORM\JoinColumn(name: 'tax_id', nullable: true, onDelete: 'SET NULL')]
    private ?Tax $tax = null;

    #[ORM\ManyToOne(targetEntity: ProductCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'SET NULL')]
    private ?ProductCategory $category = null;

    /**
     * Retired entries stay for the documents that already reference them but
     * drop out of the picker. Deleting instead would lose that history.
     */
    #[ORM\Column(name: 'active', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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

    public function getType(): ProductType
    {
        return $this->type;
    }

    public function setType(ProductType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUnit(): ProductUnit
    {
        return $this->unit;
    }

    public function setUnit(ProductUnit $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getSalePrice(): ?BigInteger
    {
        return $this->salePrice;
    }

    public function setSalePrice(?BigInteger $salePrice): self
    {
        $this->salePrice = $salePrice;

        return $this;
    }

    public function getPurchasePrice(): ?BigInteger
    {
        return $this->purchasePrice;
    }

    public function setPurchasePrice(?BigInteger $purchasePrice): self
    {
        $this->purchasePrice = $purchasePrice;

        return $this;
    }

    public function getTax(): ?Tax
    {
        return $this->tax;
    }

    public function setTax(?Tax $tax): self
    {
        $this->tax = $tax;

        return $this;
    }

    public function getCategory(): ?ProductCategory
    {
        return $this->category;
    }

    public function setCategory(?ProductCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Sale minus purchase price, or null when there is nothing to compare.
     */
    public function getMargin(): ?BigInteger
    {
        if (! $this->salePrice instanceof BigInteger || ! $this->purchasePrice instanceof BigInteger) {
            return null;
        }

        return $this->salePrice->minus($this->purchasePrice);
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
