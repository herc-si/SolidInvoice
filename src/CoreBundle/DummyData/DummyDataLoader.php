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

final readonly class DummyDataLoader
{
    /**
     * @param iterable<DummyDataLoaderInterface> $loaders
     */
    public function __construct(
        private iterable $loaders
    ) {
    }

    public function load(Company $company): void
    {
        foreach ($this->loaders as $loader) {
            $loader->load($company);
        }
    }
}
