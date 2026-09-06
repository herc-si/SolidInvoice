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

namespace Augias\SettingsBundle\DTO;

/**
 * @see \Augias\SettingsBundle\Tests\DTO\ConfigTest
 */
final readonly class Config
{
    /**
     * @param array<string, mixed> $formOptions
     */
    public function __construct(
        public string $key,
        public mixed $value,
        public ?string $description,
        public ?string $formType,
        public array $formOptions = [],
    ) {
    }
}
