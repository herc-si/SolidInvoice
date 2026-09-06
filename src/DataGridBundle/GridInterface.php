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
use Augias\DataGridBundle\GridBuilder\Batch\BatchAction;
use Augias\DataGridBundle\GridBuilder\Column\Column;
use Augias\DataGridBundle\GridBuilder\Query;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatableMessage;

interface GridInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function initialize(array $context): void;

    /**
     * @return class-string
     */
    public function entityFQCN(): string;

    /**
     * @return list<Column>
     */
    public function columns(): array;

    /**
     * @return list<Action>
     */
    public function actions(): array;

    /**
     * @return iterable<BatchAction>
     */
    public function batchActions(): iterable;

    /**
     * @return iterable<string, ColumnFilterInterface|null>
     */
    public function filters(): iterable;

    public function query(EntityManagerInterface $entityManager, Query $query): Query;

    /**
     * Returns the route name for creating a new entity.
     * Used for the empty state CTA button.
     */
    public function getCreateRoute(): ?string;

    /**
     * Returns the label for the create button in the empty state.
     */
    public function getCreateLabel(): ?TranslatableMessage;

    /**
     * Returns the empty-state heading shown when the grid has no rows and no
     * active search or filter. The base grid returns generic copy; override to
     * make it entity-specific.
     */
    public function getEmptyTitle(): TranslatableMessage;

    /**
     * Returns the empty-state description shown beneath the heading on a
     * genuinely empty (first-run) grid. The base grid returns generic copy;
     * override to make it entity-specific.
     */
    public function getEmptyDescription(): TranslatableMessage;

    /**
     * Returns true if this grid supports expandable row details.
     */
    public function hasRowDetails(): bool;

    /**
     * Returns the template path for rendering expandable row details.
     * Only used if hasRowDetails() returns true.
     */
    public function getRowDetailTemplate(): ?string;
}
