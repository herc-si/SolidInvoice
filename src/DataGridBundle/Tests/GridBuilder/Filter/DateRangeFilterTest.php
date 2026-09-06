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

namespace Augias\DataGridBundle\Tests\GridBuilder\Filter;

use Augias\DataGridBundle\Form\Type\DateRangeFormType;
use Augias\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use Doctrine\ORM\QueryBuilder;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DateRangeFilterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private DateRangeFilter $filter;

    private QueryBuilder & MockObject $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->filter = new DateRangeFilter('field');
    }

    public function formReturnsCorrectType(): void
    {
        self::assertSame(DateRangeFormType::class, $this->filter->form());
    }

    public function testFilterAddsCorrectConditionsWhenStartAndEndArePresent(): void
    {
        $this->queryBuilder
            ->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->exactly(2))
            ->method('setParameter');

        $this->filter->filter($this->queryBuilder, ['start' => '2022-01-01', 'end' => '2022-01-31']);
    }

    public function testFilterAddsCorrectConditionWhenOnlyStartIsPresent(): void
    {
        $this->queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter');

        $this->filter->filter($this->queryBuilder, ['start' => '2022-01-01']);
    }

    public function testFilterAddsCorrectConditionWhenOnlyEndIsPresent(): void
    {
        $this->queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('setParameter');

        $this->filter->filter($this->queryBuilder, ['end' => '2022-01-31']);
    }

    public function testFilterDoesNotAddAnyConditionWhenStartAndEndAreNotPresent(): void
    {
        $this->queryBuilder
            ->expects($this->never())
            ->method('andWhere');

        $this->queryBuilder
            ->expects($this->never())
            ->method('setParameter');

        $this->filter->filter($this->queryBuilder, []);
    }
}
