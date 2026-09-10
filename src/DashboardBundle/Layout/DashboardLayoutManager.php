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

namespace Augias\DashboardBundle\Layout;

use Augias\DashboardBundle\WidgetFactory;
use Augias\DashboardBundle\Widgets\WidgetDefinition;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Enum\UserSettingType;
use Augias\UserBundle\Repository\UserSettingRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Exception\ORMException;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Reads and writes the current user's dashboard layout.
 *
 * The layout is one JSON document in `user_settings`, not a table of rows. It is
 * always read and written whole, it is never queried across users, and it is
 * worthless without the widget registry to interpret it — so a row per widget
 * would buy nothing but joins.
 *
 * A note on scope: `user_settings` is keyed by (user, key) with no company
 * column, and a user can belong to several companies and switch between them.
 * One layout therefore follows the user across all their companies. That is a
 * deliberate choice, not an oversight — arrangement is a personal preference and
 * the widgets themselves are already company-filtered — but it is the thing to
 * revisit first if per-company dashboards are ever asked for.
 *
 * @see \Augias\DashboardBundle\Tests\Layout\DashboardLayoutManagerTest
 */
final readonly class DashboardLayoutManager
{
    public function __construct(
        private UserSettingRepository $userSettingRepository,
        private WidgetFactory $factory,
        private Security $security,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * The stored layout, or null to mean "never customised, use the defaults".
     *
     * Null is also what a broken or unreadable setting returns. Falling back to
     * the default dashboard beats an error page: the user still gets their
     * numbers, and the next save overwrites whatever was corrupt.
     */
    public function load(): ?DashboardLayout
    {
        $user = $this->currentUser();

        if (! $user instanceof User) {
            return null;
        }

        try {
            $setting = $this->userSettingRepository->getSetting($user, UserSettingType::DashboardLayout);
        } catch (DBALException | ORMException $e) {
            $this->logger->error('Unable to read the dashboard layout', ['exception' => $e]);

            return null;
        }

        $value = $setting?->getValue();

        if (null === $value || '' === $value) {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logger->error('The stored dashboard layout is not valid JSON', ['exception' => $e]);

            return null;
        }

        return is_array($decoded) ? DashboardLayout::fromArray($decoded) : null;
    }

    /**
     * @throws JsonException
     */
    public function save(DashboardLayout $layout): void
    {
        $user = $this->currentUser();

        if (! $user instanceof User) {
            return;
        }

        $this->userSettingRepository->saveSetting(
            $user,
            UserSettingType::DashboardLayout,
            json_encode($this->sanitize($layout)->toArray(), JSON_THROW_ON_ERROR),
        );
    }

    public function reset(): void
    {
        $user = $this->currentUser();

        if ($user instanceof User) {
            $this->userSettingRepository->removeSetting($user, UserSettingType::DashboardLayout);
        }
    }

    /**
     * Reconcile a layout with the registry before it is stored.
     *
     * {@see LayoutResolver} already tolerates a stale layout at read time, so
     * this is not what keeps the page working. It is what stops the stored
     * document from accumulating ids of widgets that no longer exist, which
     * would otherwise grow forever and make the row harder to read than the
     * dashboard it describes.
     *
     * Unknown ids are dropped. A pinned widget is forced back into view. A
     * widget the payload never mentioned is left out of both lists so that
     * {@see LayoutResolver} still treats it as new rather than as hidden.
     */
    private function sanitize(DashboardLayout $layout): DashboardLayout
    {
        $visible = [];
        $hidden = [];

        foreach ($layout->visible as $entry) {
            if ($this->factory->has($entry['id'])) {
                $visible[] = $entry;
            }
        }

        $pinned = array_flip(array_column($visible, 'id'));

        foreach ($layout->hidden as $id) {
            $definition = $this->factory->get($id);

            if (! $definition instanceof WidgetDefinition) {
                continue;
            }

            if (! $definition->removable) {
                if (! isset($pinned[$id])) {
                    $visible[] = ['id' => $id, 'zone' => $definition->zone];
                }

                continue;
            }

            $hidden[] = $id;
        }

        return new DashboardLayout($visible, $hidden);
    }

    private function currentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }
}
