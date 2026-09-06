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

namespace Augias\CoreBundle\Tests\Email;

use Augias\CoreBundle\Email\NullEmailVerificationGate;
use Augias\CoreBundle\Entity\Company;
use PHPUnit\Framework\TestCase;

final class NullEmailVerificationGateTest extends TestCase
{
    public function testIsGatedAlwaysFalse(): void
    {
        self::assertFalse(new NullEmailVerificationGate()->isGated());
    }

    public function testIsCompanyGatedAlwaysFalse(): void
    {
        self::assertFalse(new NullEmailVerificationGate()->isCompanyGated(new Company()));
    }

    public function testReasonReturnsEmptyString(): void
    {
        self::assertSame('', new NullEmailVerificationGate()->reason('send invoice'));
    }
}
