<?php

/*
 * This file is part of Augias project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Augias\AppMode;
use Augias\Kernel;
use Augias\Runtime;

$_SERVER['APP_RUNTIME'] = Runtime::class;
$_SERVER['APP_RUNTIME_OPTIONS'] = [
    'env_var_name' => 'AUGIAS_ENV',
    'debug_var_name' => 'AUGIAS_DEBUG'
];

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return static function (AppMode $mode, array $context) {
    return new Kernel($mode, $context['AUGIAS_ENV'], (bool) $context['AUGIAS_DEBUG']);
};
