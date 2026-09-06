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

namespace Augias\CoreBundle\Enum;

/**
 * Locales Augias ships translated UI catalogs for (see translations/*.<locale>.yml).
 * This is a subset of the wider push/pull locale list in config/packages/translation.php,
 * which also includes locales that only exist on the translation provider.
 */
enum SupportedLocale: string
{
    case English = 'en';
    case French = 'fr';

    /**
     * The language's own name, in that language — never translated.
     */
    public function nativeName(): string
    {
        return match ($this) {
            self::English => 'English',
            self::French => 'Français',
        };
    }
}
