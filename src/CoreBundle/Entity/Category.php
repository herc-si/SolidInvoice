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

namespace Augias\CoreBundle\Entity;

use Augias\CoreBundle\Enum\CategoryUsage;
use Augias\CoreBundle\Repository\CategoryRepository;
use Augias\CoreBundle\Traits\Entity\CompanyAware;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * One list of categories for the whole company.
 *
 * Purchases and the catalogue used to have a table each — `bill_categories` and
 * `product_categories` — with byte-for-byte identical shapes and two adjacent
 * settings screens both called "categories". They are one list now.
 *
 * What is *not* merged is where each one applies: {@see $usedForPurchases} and
 * {@see $usedForCatalog} keep the dropdowns honest, because the two sides
 * classify opposite halves of the business. A category may carry both, which is
 * the whole point — "Hardware" that you buy and also resell is one record.
 *
 * The usages are two boolean columns rather than a set stored as JSON so that
 * "the categories usable here" stays an ordinary indexed WHERE clause on both
 * MySQL and the SQLite used by the tests. A third side, should one ever appear,
 * is one more column.
 *
 * It lives in CoreBundle rather than in either bundle that uses it: neither
 * purchases nor the catalogue owns it, and putting it in one would make the
 * other depend on it for no reason.
 *
 * @see \Augias\CoreBundle\Tests\Entity\CategoryTest
 */
#[ORM\Table(name: Category::TABLE_NAME)]
#[ORM\UniqueConstraint(columns: ['name', 'company_id'])]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category implements Stringable
{
    final public const string TABLE_NAME = 'categories';

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

    #[ORM\Column(name: 'used_for_purchases', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $usedForPurchases = false;

    #[ORM\Column(name: 'used_for_catalog', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $usedForCatalog = false;

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

    public function isUsedForPurchases(): bool
    {
        return $this->usedForPurchases;
    }

    public function setUsedForPurchases(bool $usedForPurchases): self
    {
        $this->usedForPurchases = $usedForPurchases;

        return $this;
    }

    public function isUsedForCatalog(): bool
    {
        return $this->usedForCatalog;
    }

    public function setUsedForCatalog(bool $usedForCatalog): self
    {
        $this->usedForCatalog = $usedForCatalog;

        return $this;
    }

    public function supports(CategoryUsage $usage): bool
    {
        return match ($usage) {
            CategoryUsage::Purchase => $this->usedForPurchases,
            CategoryUsage::Catalog => $this->usedForCatalog,
        };
    }

    public function setUsage(CategoryUsage $usage, bool $enabled): self
    {
        return match ($usage) {
            CategoryUsage::Purchase => $this->setUsedForPurchases($enabled),
            CategoryUsage::Catalog => $this->setUsedForCatalog($enabled),
        };
    }

    /**
     * @return list<CategoryUsage>
     */
    public function usages(): array
    {
        $usages = [];

        foreach (CategoryUsage::cases() as $usage) {
            if ($this->supports($usage)) {
                $usages[] = $usage;
            }
        }

        return $usages;
    }

    /**
     * A category that applies nowhere would be invisible in every dropdown
     * while still occupying the list — almost certainly a mistake rather than
     * an intent, so the form refuses it.
     */
    #[Assert\IsTrue(message: 'category.constraint.at_least_one_usage')]
    public function hasAtLeastOneUsage(): bool
    {
        return $this->usedForPurchases || $this->usedForCatalog;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
