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

/**
 * What still has to happen before a period that has ended is declared.
 *
 * Two steps, in order, and the user has to be told which one they are on:
 * "close the quarter" and "file the quarter" are different jobs, on different
 * screens, and a card that lumps them into "declaration outstanding" leaves the
 * user to work out which. Augias never files anything itself — {@see self::File}
 * means copying the figures onto the collecting body's own site and coming back
 * to record the reference.
 */
enum DeclarationAction: string
{
    /** The period has ended but its entries are still editable. */
    case Close = 'close';

    /** The books are sealed and the figures are final; nothing left but to file. */
    case File = 'file';

    public function translationKey(): string
    {
        return 'dashboard.accounting.action.' . $this->value;
    }
}
