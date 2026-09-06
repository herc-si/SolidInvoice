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

namespace Augias\InstallBundle\Tests\Step;

use Augias\InstallBundle\Step\CreateDatabaseStep;
use Augias\InstallBundle\Step\InstallationStepInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreateDatabaseStep::class)]
final class CreateDatabaseStepTest extends TestCase
{
    public function testPriority(): void
    {
        self::assertSame(20, CreateDatabaseStep::priority());
    }

    public function testGetLabel(): void
    {
        self::assertSame('Creating database', CreateDatabaseStep::getLabel());
    }

    public function testStepImplementsInstallationStepInterface(): void
    {
        self::assertTrue(is_a(CreateDatabaseStep::class, InstallationStepInterface::class, true));
    }
}
