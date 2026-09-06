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

namespace Augias\CoreBundle\Templates;

enum BillingDocumentType: string
{
    case Invoice = 'invoice';
    case Quote = 'quote';

    public function twigNamespace(): string
    {
        return match ($this) {
            self::Invoice => '@AugiasInvoice',
            self::Quote => '@AugiasQuote',
        };
    }
}
