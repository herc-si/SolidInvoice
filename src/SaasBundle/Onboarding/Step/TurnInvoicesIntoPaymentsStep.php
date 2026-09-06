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

use Augias\PaymentBundle\Repository\PaymentRepository;
use Augias\SaasBundle\Onboarding\OnboardingContext;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTaggedItem(priority: 70)]
final class TurnInvoicesIntoPaymentsStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly PaymentRepository $paymentRepository,
    ) {
        parent::__construct($translator);
    }

    public static function key(): string
    {
        return 'turn_invoices_into_payments';
    }

    public static function priority(): int
    {
        return 70;
    }

    #[Override]
    public function shouldSend(OnboardingContext $context): bool
    {
        return $this->paymentRepository->count(['company' => $context->company]) === 0;
    }
}
