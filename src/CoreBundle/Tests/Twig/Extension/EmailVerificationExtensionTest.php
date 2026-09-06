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

namespace Augias\CoreBundle\Tests\Twig\Extension;

use Augias\CoreBundle\Contracts\EmailVerificationGateInterface;
use Augias\CoreBundle\Twig\Extension\EmailVerificationExtension;
use PHPUnit\Framework\TestCase;

final class EmailVerificationExtensionTest extends TestCase
{
    public function testIsGatedDelegatesToGate(): void
    {
        $gate = $this->createMock(EmailVerificationGateInterface::class);
        $gate->expects(self::once())->method('isGated')->willReturn(true);

        self::assertTrue(new EmailVerificationExtension($gate)->isEmailVerificationGated());
    }

    public function testMessageDelegatesToGate(): void
    {
        $gate = $this->createMock(EmailVerificationGateInterface::class);
        $gate->expects(self::once())
            ->method('reason')
            ->with('sending this invoice')
            ->willReturn('Please verify your email address before sending this invoice.');

        self::assertSame(
            'Please verify your email address before sending this invoice.',
            new EmailVerificationExtension($gate)->emailVerificationMessage('sending this invoice'),
        );
    }
}
