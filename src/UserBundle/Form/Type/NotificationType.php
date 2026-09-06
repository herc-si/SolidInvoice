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

namespace Augias\UserBundle\Form\Type;

use Augias\NotificationBundle\Notification\NotificationMessage;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
final class NotificationType extends AbstractType
{
    /**
     * @param ServiceLocator<NotificationMessage> $notificationList
     */
    public function __construct(
        #[AutowireLocator('augias_notification.notification', 'name')]
        private readonly ServiceLocator $notificationList,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $notificationEvents = array_keys($this->notificationList->getProvidedServices());

        foreach ($notificationEvents as $event) {
            $builder->add(
                $event,
                NotificationSettingType::class,
                [
                    'event' => $event,
                ]
            );
        }
    }
}
