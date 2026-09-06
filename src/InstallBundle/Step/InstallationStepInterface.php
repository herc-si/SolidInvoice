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

use Augias\InstallBundle\DTO\Installation;
use Generator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(InstallationStepInterface::DI_TAG)]
interface InstallationStepInterface
{
    public const string DI_TAG = 'augias.installation_step';

    public static function priority(): int;

    public function execute(Installation $installationData, ?callable $callback = null): Generator;

    public static function getLabel(): string;
}
