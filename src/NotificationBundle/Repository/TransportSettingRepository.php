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

namespace Augias\NotificationBundle\Repository;

use Augias\NotificationBundle\Entity\TransportSetting;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<TransportSetting>
 */
final class TransportSettingRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransportSetting::class);
    }

    public function delete(TransportSetting $setting): void
    {
        $this->getEntityManager()->remove($setting);
        $this->getEntityManager()->flush();
    }
}
