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

namespace Augias\InstallBundle\Step;

use Augias\CoreBundle\AugiasCoreBundle;
use Augias\CoreBundle\Entity\Version;
use Augias\CoreBundle\Repository\VersionRepository;
use Augias\InstallBundle\DTO\Installation;
use Augias\InstallBundle\Installer\Database\Migration;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * @see \Augias\InstallBundle\Tests\Step\RunMigrationsStepTest
 */
#[AsTaggedItem('Creating database schema', priority: 10)]
final readonly class RunMigrationsStep implements InstallationStepInterface
{
    public function __construct(
        private Migration $migration,
        private ManagerRegistry $registry,
    ) {
    }

    public static function priority(): int
    {
        return 10;
    }

    public function execute(Installation $installationData, ?callable $callback = null): Generator
    {
        yield from $this->migration->migrate($callback);

        $version = AugiasCoreBundle::VERSION;

        $entityManager = $this->registry->getManager();

        /** @var VersionRepository $repository */
        $repository = $entityManager->getRepository(Version::class);

        $repository->updateVersion($version);
    }

    public static function getLabel(): string
    {
        return 'Creating database schema';
    }
}
