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

namespace Augias\SaasBundle\Onboarding\Step;

use Augias\SaasBundle\Onboarding\OnboardingContext;
use Augias\SettingsBundle\SystemConfig;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTaggedItem(priority: 60)]
final class CustomizeLogoStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly SystemConfig $systemConfig,
    ) {
        parent::__construct($translator);
    }

    public static function key(): string
    {
        return 'customize_logo';
    }

    public static function priority(): int
    {
        return 60;
    }

    #[Override]
    public function shouldSend(OnboardingContext $context): bool
    {
        $logo = $this->systemConfig->get('system/company/logo');

        return $logo === null || $logo === '';
    }
}
