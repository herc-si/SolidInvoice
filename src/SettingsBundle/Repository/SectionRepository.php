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

namespace Augias\SettingsBundle\Repository;

use Augias\SettingsBundle\Entity\Setting;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<Setting>
 */
class SectionRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * Returns an array of all the top-level sections.
     *
     * @param string $cacheKey
     * @return list<Setting>
     */
    public function getTopLevelSections(bool $cache = false, $cacheKey = 'augias_settings_top_section_sections', int $lifetime = 604800): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.parent IS NULL');

        $query = $qb->getQuery();

        if ($cache) {
            $query->enableResultCache($lifetime, $cacheKey);
        }

        return $query->getResult();
    }
}
