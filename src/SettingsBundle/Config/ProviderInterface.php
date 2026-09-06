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

namespace Augias\SettingsBundle\Config;

use Augias\SettingsBundle\DTO\Config;

interface ProviderInterface
{
    /**
     * @param array{company_name?: string|null, currency?: string|null, locale?: string|null} $data
     *
     * @return Config[]
     */
    public function provide(array $data): array;
}
