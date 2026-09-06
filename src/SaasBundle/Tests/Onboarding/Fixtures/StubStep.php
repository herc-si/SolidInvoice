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

namespace Augias\SaasBundle\Tests\Onboarding\Fixtures;

use Augias\SaasBundle\Onboarding\OnboardingContext;
use Augias\SaasBundle\Onboarding\OnboardingEmailStepInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

/**
 * Base for test-only step stubs. Each concrete subclass hardcodes its key and
 * priority so static resolution returns the correct value per class.
 */
abstract class StubStep implements OnboardingEmailStepInterface
{
    public function shouldSend(OnboardingContext $context): bool
    {
        return true;
    }

    public function createEmail(OnboardingContext $context): TemplatedEmail
    {
        return new TemplatedEmail();
    }
}
