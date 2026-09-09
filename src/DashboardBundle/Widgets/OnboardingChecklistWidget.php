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

namespace Augias\DashboardBundle\Widgets;

use Augias\DashboardBundle\Attribute\AsDashboardWidget;
use Augias\DashboardBundle\Checklist\ChecklistManager;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\UserBundle\Entity\User;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @see \Augias\DashboardBundle\Tests\Widgets\OnboardingChecklistWidgetTest
 */
#[AsDashboardWidget(
    id: 'onboarding_checklist',
    label: 'dashboard.widget.onboarding_checklist',
    icon: 'tabler:rocket',
    zone: WidgetZone::Top,
    priority: 300,
)]
final readonly class OnboardingChecklistWidget implements WidgetInterface
{
    public function __construct(
        private ChecklistManager $checklistManager,
        private Security $security,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Dismissal is a per-user setting, so it belongs here rather than inside
     * getData(): a dismissed checklist now costs one cheap settings read instead
     * of running every item's isComplete() query only to throw the answer away.
     *
     * A failed read reports "supported" so the widget renders and shows its own
     * error state. Returning false would make an outage look like a checklist
     * the user had already finished.
     */
    public function supports(): bool
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return false;
        }

        try {
            return $this->checklistManager->shouldShow($user);
        } catch (DBALException | ORMException $e) {
            $this->logger->error('Unable to read the onboarding checklist visibility', ['exception' => $e]);

            return true;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        try {
            $progress = $this->checklistManager->getProgress();
        } catch (DBALException | ORMException $e) {
            $this->logger->error('Unable to load the onboarding checklist progress', ['exception' => $e]);

            // Render an error state rather than vanishing: a checklist that
            // silently disappears is indistinguishable from a completed one.
            return ['show' => true, 'error' => true];
        }

        if ([] === $progress->items) {
            return ['show' => false];
        }

        return [
            'show' => true,
            'error' => false,
            'progress' => $progress,
        ];
    }

    public function getTemplate(): string
    {
        return '@AugiasDashboard/Widget/onboarding_checklist.html.twig';
    }
}
