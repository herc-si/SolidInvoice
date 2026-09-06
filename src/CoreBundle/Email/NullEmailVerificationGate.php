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

namespace Augias\CoreBundle\Email;

use Augias\CoreBundle\Contracts\EmailVerificationGateInterface;
use Augias\CoreBundle\Entity\Company;

/**
 * @see \Augias\CoreBundle\Tests\Email\NullEmailVerificationGateTest
 */
final class NullEmailVerificationGate implements EmailVerificationGateInterface
{
    public function isGated(): bool
    {
        return false;
    }

    public function isCompanyGated(Company $company): bool
    {
        return false;
    }

    public function reason(string $action): string
    {
        return '';
    }
}
