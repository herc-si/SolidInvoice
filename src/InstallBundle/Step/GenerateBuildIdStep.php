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

use Augias\CoreBundle\ConfigWriter;
use Augias\InstallBundle\DTO\Installation;
use Generator;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Uid\Uuid;

/**
 * @see \Augias\InstallBundle\Tests\Step\GenerateBuildIdStepTest
 */
#[AsTaggedItem('Generating build id', priority: 25)]
final readonly class GenerateBuildIdStep implements InstallationStepInterface
{
    public function __construct(
        private ConfigWriter $configWriter,
    ) {
    }

    public static function priority(): int
    {
        return 25;
    }

    public function execute(Installation $installationData, ?callable $callback = null): Generator
    {
        $buildId = (string) Uuid::v7();

        $this->configWriter->save(['BUILD_ID' => $buildId]);

        if ($callback !== null) {
            yield from $callback(sprintf('Build ID generated: %s', $buildId));
        }
    }

    public static function getLabel(): string
    {
        return 'Generating build id';
    }
}
