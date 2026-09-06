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

namespace Augias\CoreBundle\Tests\Functional;

use Augias\CoreBundle\Contracts\EmailVerificationGateInterface;
use Augias\CoreBundle\Email\NullEmailVerificationGate;
use Augias\CoreBundle\Entity\Company;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EmailVerificationGateBindingTest extends KernelTestCase
{
    private function skipIfSaasLoaded(): void
    {
        $bundles = self::$kernel->getBundles();

        if (isset($bundles['SolidWorxPlatformSaasBundle']) || isset($bundles['AugiasSaasBundle'])) {
            self::markTestSkipped('Test kernel has SaaS bundles loaded; this regression test is for non-SaaS mode only.');
        }
    }

    public function testNullGateIsBoundWhenSaasBundleNotLoaded(): void
    {
        self::bootKernel();
        $this->skipIfSaasLoaded();

        $gate = self::getContainer()->get(EmailVerificationGateInterface::class);
        self::assertInstanceOf(NullEmailVerificationGate::class, $gate);
    }

    public function testNullGateIsGatedReturnsFalse(): void
    {
        self::bootKernel();
        $this->skipIfSaasLoaded();

        $gate = self::getContainer()->get(EmailVerificationGateInterface::class);
        self::assertFalse($gate->isGated());
    }

    public function testNullGateIsCompanyGatedReturnsFalse(): void
    {
        self::bootKernel();
        $this->skipIfSaasLoaded();

        $gate = self::getContainer()->get(EmailVerificationGateInterface::class);
        self::assertFalse($gate->isCompanyGated(new Company()));
    }

    public function testNullGateReasonReturnsEmptyString(): void
    {
        self::bootKernel();
        $this->skipIfSaasLoaded();

        $gate = self::getContainer()->get(EmailVerificationGateInterface::class);
        self::assertSame('', $gate->reason('do anything'));
    }
}
