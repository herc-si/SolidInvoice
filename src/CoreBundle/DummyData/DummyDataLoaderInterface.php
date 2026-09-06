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

namespace Augias\CoreBundle\DummyData;

use Augias\CoreBundle\Entity\Company;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('augias.dummy_data_loader')]
interface DummyDataLoaderInterface
{
    public function load(Company $company): void;

    public static function getPriority(): int;
}
