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

namespace Augias\CoreBundle\Twig\Extension;

use Augias\CoreBundle\Contracts\EmailVerificationGateInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * @see \Augias\CoreBundle\Tests\Twig\Extension\EmailVerificationExtensionTest
 */
final readonly class EmailVerificationExtension
{
    public function __construct(
        private EmailVerificationGateInterface $gate,
    ) {
    }

    #[AsTwigFunction(name: 'is_email_verification_gated')]
    public function isEmailVerificationGated(): bool
    {
        return $this->gate->isGated();
    }

    #[AsTwigFunction(name: 'email_verification_message')]
    public function emailVerificationMessage(string $action): string
    {
        return $this->gate->reason($action);
    }
}
