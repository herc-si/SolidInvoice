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

namespace Augias\UserBundle\Enum;

enum UserSettingType: string
{
    case Timezone = 'timezone';
    case Location = 'location';
    case Locale = 'locale';
    case OnboardComplete = 'onboard_complete';
    case OnboardingStep = 'onboarding_step';
    case OnboardingSkipped = 'onboarding_skipped';
    case OnboardingStartedAt = 'onboarding_started_at';
    case OnboardingCompletedAt = 'onboarding_completed_at';
    case OnboardingChecklistDismissed = 'onboarding_checklist_dismissed';
    case OnboardingEmailSequenceLastStep = 'onboarding_email_sequence_last_step';
}
