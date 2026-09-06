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

namespace Augias\UserBundle\Tests\Security;

use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Security\UserChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\InMemoryUser;

#[CoversClass(UserChecker::class)]
final class UserCheckerTest extends TestCase
{
    public function testThrowsForDisabledUser(): void
    {
        $user = new User()->setEnabled(false);

        $this->expectException(DisabledException::class);

        new UserChecker()->checkPreAuth($user);
    }

    public function testAllowsEnabledUser(): void
    {
        $user = new User()->setEnabled(true);

        $checker = new UserChecker();
        $checker->checkPreAuth($user);
        $checker->checkPostAuth($user);

        $this->expectNotToPerformAssertions();
    }

    public function testIgnoresNonPlatformUser(): void
    {
        // Other UserInterface implementations (e.g. system tokens) are not our concern.
        $checker = new UserChecker();
        $checker->checkPreAuth(new InMemoryUser('someone', null));

        $this->expectNotToPerformAssertions();
    }
}
