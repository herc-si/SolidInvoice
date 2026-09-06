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

namespace Augias\DataGridBundle;

use Augias\DataGridBundle\Filter\ColumnFilterInterface;
use Augias\DataGridBundle\GridBuilder\Action\Action;
use Augias\DataGridBundle\GridBuilder\Column\Column;
use Augias\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use Augias\DataGridBundle\GridBuilder\Column\StringColumn;
use Augias\DataGridBundle\GridBuilder\Query;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @see \Augias\DataGridBundle\Tests\GridTest
 */
abstract class Grid implements GridInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * @param array<string, mixed> $context
     */
    public function initialize(array $context): void
    {
        $this->context = $context;
    }

    /**
     * @return list<Column>
     * @throws ReflectionException
     */
    public function columns(): array
    {
        $columns = [];

        foreach (new ReflectionClass($this->entityFQCN())->getProperties() as $property) {
            $type = $property->hasType() ? $property->getType() : null;

            if ($type instanceof ReflectionNamedType) {
                $columns[] = match ($type->getName()) {
                    DateTimeInterface::class, DateTime::class, DateTimeImmutable::class => DateTimeColumn::new($property->getName()),
                    default => StringColumn::new($property->getName()),
                };
            } else {
                $columns[] = StringColumn::new($property->getName());
            }
        }

        return $columns;
    }

    /**
     * @return list<Action>
     */
    public function actions(): array
    {
        return [];
    }

    public function batchActions(): iterable
    {
        return [];
    }

    /**
     * @return iterable<string, ColumnFilterInterface|null>
     * @throws ReflectionException
     */
    public function filters(): iterable
    {
        foreach ($this->columns() as $column) {
            if ($column->getFilter() instanceof ColumnFilterInterface) {
                yield $column->getField() => $column->getFilter();
            }
        }
    }

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        return $query;
    }

    /**
     * Returns the route name for creating a new entity.
     * Override this method in your grid to enable the empty state CTA.
     */
    public function getCreateRoute(): ?string
    {
        return null;
    }

    /**
     * Returns the label for the create button in the empty state.
     */
    public function getCreateLabel(): ?TranslatableMessage
    {
        return new TranslatableMessage('Create');
    }

    /**
     * Generic default. Override in a grid to give its first-run empty state
     * entity-specific copy.
     */
    public function getEmptyTitle(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.no_results.label');
    }

    /**
     * Generic default. Override in a grid to give its first-run empty state
     * entity-specific copy.
     */
    public function getEmptyDescription(): TranslatableMessage
    {
        return new TranslatableMessage('datagrid.no_results.empty');
    }

    /**
     * Returns true if this grid supports expandable row details.
     */
    public function hasRowDetails(): bool
    {
        return false;
    }

    /**
     * Returns the template path for rendering expandable row details.
     * Only used if hasRowDetails() returns true.
     */
    public function getRowDetailTemplate(): ?string
    {
        return null;
    }
}
