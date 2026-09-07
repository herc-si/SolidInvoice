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

namespace Augias\BillBundle\Model;

final class Graph
{
    public const string TRANSITION_CONFIRM = 'confirm';

    public const string TRANSITION_CANCEL = 'cancel';

    public const string TRANSITION_OVERDUE = 'overdue';

    public const string TRANSITION_PAY = 'pay';

    public const string TRANSITION_REOPEN = 'reopen';

    public const string TRANSITION_ARCHIVE = 'archive';

    public const string TRANSITION_EDIT = 'edit';
}
