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

namespace Augias\CoreBundle\Enum\Menu;

/**
 * Sidebar order, highest first. Grouped so the sales chain (quote -> invoice ->
 * payment) reads top to bottom, then purchases, and finally the records and
 * configuration everything else hangs off.
 */
enum MenuPriority: int
{
    case PRIORITY_DASHBOARD = 100;

    case PRIORITY_QUOTE = 90;

    case PRIORITY_INVOICE = 85;

    case PRIORITY_PAYMENT = 75;

    case PRIORITY_BILL = 55;

    case PRIORITY_CATALOG = 45;

    case PRIORITY_CLIENT = 40;

    case PRIORITY_SYSTEM = 10;
}
