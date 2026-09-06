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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'framework' => [
        'secret' => env('AUGIAS_APP_SECRET'),
        'php_errors' => [
            'log' => true,
        ],
        'trusted_headers' => [
            'x-forwarded-for',
            'x-forwarded-proto',
            'x-forwarded-port',
            'x-forwarded-host',
            'x-forwarded-prefix',
        ],
        'session' => [
            'name' => 'AUGIAS_APP',
        ],
        'secrets' => [
            'enabled' => true,
            'vault_directory' => env('AUGIAS_CONFIG_DIR'),
        ],
    ],
]);
