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

namespace Augias\AccountingBundle\Enum;

use Augias\AccountingBundle\Service\ThresholdMonitor;

/**
 * How close to a limit a company has got, as a name rather than a percentage.
 *
 * Two screens were comparing against the bare numbers 80 and 100 in Twig, and
 * {@see ThresholdMonitor::STEPS} raised its alerts at the same two written out
 * again. A bar that turns amber and an email saying the company is nearing a
 * limit must not be able to disagree about what "nearing" means, so the
 * boundaries are named here and the monitor's steps are built from them.
 *
 * The cases say what is true, not what colour to paint. Which class a template
 * picks for {@see self::Exceeded} is a presentation decision and stays in the
 * template.
 *
 * @see \Augias\AccountingBundle\Tests\Enum\LimitSeverityTest
 */
enum LimitSeverity: string
{
    case Ok = 'ok';
    case Nearing = 'nearing';
    case Exceeded = 'exceeded';

    /**
     * Past the limit. Not "at" it: a limit is exceeded once turnover reaches
     * 100% of it, which is the point the consequences attach.
     */
    public const int EXCEEDED_AT = 100;

    /**
     * Close enough that there is still time to do something about it.
     */
    public const int NEARING_AT = 80;

    /**
     * @param int $used percentage of the limit consumed
     */
    public static function forUsage(int $used): self
    {
        return match (true) {
            $used >= self::EXCEEDED_AT => self::Exceeded,
            $used >= self::NEARING_AT => self::Nearing,
            default => self::Ok,
        };
    }
}
