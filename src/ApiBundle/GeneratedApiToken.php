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

namespace Augias\ApiBundle;

use Augias\UserBundle\Entity\ApiToken;
use SensitiveParameter;

/**
 * The plaintext token is only available at creation time — it is never
 * persisted nor recoverable from the database afterwards.
 */
final readonly class GeneratedApiToken
{
    public function __construct(
        #[SensitiveParameter]
        public ApiToken $token,
        public string $plaintext,
    ) {
    }
}
