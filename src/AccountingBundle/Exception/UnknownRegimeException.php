<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\AccountingBundle\Exception;

use InvalidArgumentException;
use function implode;
use function sprintf;

/**
 * Thrown when a regime code has no implementation behind it — a stale settings
 * value, or a typo in a command argument.
 *
 * Callers that can carry on without a regime should ask
 * {@see \Augias\AccountingBundle\Regime\RegimeRegistry::forProfile()} instead,
 * which returns null rather than throwing.
 */
final class UnknownRegimeException extends InvalidArgumentException
{
    /**
     * @param list<string> $known
     */
    public static function forCode(string $code, array $known): self
    {
        return new self(sprintf(
            'No accounting regime is registered under the code "%s". Registered regimes: %s.',
            $code,
            [] === $known ? '(none)' : implode(', ', $known),
        ));
    }
}
