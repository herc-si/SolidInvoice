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

namespace Augias\NotificationBundle\Tests\DependencyInjection;

use Augias\NotificationBundle\DependencyInjection\AugiasNotificationExtension;
use Augias\NotificationBundle\Notification\NotificationManager;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

final class AugiasNotificationExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @return AugiasNotificationExtension[]
     */
    protected function getContainerExtensions(): array
    {
        return [new AugiasNotificationExtension()];
    }

    public function testLoad(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(NotificationManager::class);
    }
}
