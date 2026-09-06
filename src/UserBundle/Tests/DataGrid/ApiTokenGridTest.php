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

namespace Augias\UserBundle\Tests\DataGrid;

use Augias\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use Augias\DataGridBundle\GridBuilder\Column\StringColumn;
use Augias\UserBundle\DataGrid\ApiTokenGrid;
use Augias\UserBundle\Entity\ApiToken;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class ApiTokenGridTest extends TestCase
{
    private function createGrid(): ApiTokenGrid
    {
        return new ApiTokenGrid($this->createStub(Security::class));
    }

    public function testEntityFQCNReturnsApiTokenClass(): void
    {
        $grid = $this->createGrid();

        self::assertSame(ApiToken::class, $grid->entityFQCN());
    }

    public function testColumnsReturnsCorrectConfiguration(): void
    {
        $grid = $this->createGrid();
        $columns = $grid->columns();

        self::assertCount(5, $columns);
        self::assertInstanceOf(StringColumn::class, $columns[0]);
        self::assertInstanceOf(StringColumn::class, $columns[1]);
        self::assertInstanceOf(StringColumn::class, $columns[2]);
        self::assertInstanceOf(RelativeDateColumn::class, $columns[3]);
        self::assertInstanceOf(RelativeDateColumn::class, $columns[4]);
    }

    public function testActionsReturnsViewHistoryAction(): void
    {
        $grid = $this->createGrid();
        $actions = $grid->actions();

        self::assertCount(1, $actions);
        self::assertSame('View History', $actions[0]->getLabel());
        self::assertSame('history', $actions[0]->getIcon());
    }
}
