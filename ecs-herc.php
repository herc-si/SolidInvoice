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

use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

/*
 * File headers for the bundles HERC SI wrote.
 *
 * Augias forks SolidInvoice, so the tree holds code from two authors. Files
 * inherited from upstream keep their original attribution — that is what the
 * MIT licence requires, and rewriting someone else's copyright notice is not
 * something a fork gets to do. The bundles below did not come from upstream;
 * they were written for Augias and are attributed accordingly.
 *
 * This needs its own config because HeaderCommentFixer takes a single header
 * string, so one ECS run cannot stamp two different ones. The main ecs.php
 * therefore skips HeaderCommentFixer for exactly these paths and everything
 * else about them — every other rule — still runs from there. Both configs are
 * run together by `composer cs` and `composer cs-fix`, and by CI.
 *
 * A new bundle written for Augias belongs in the list below AND in ecs.php's
 * HeaderCommentFixer skip list. Miss the second and the two configs fight over
 * the file on every run.
 *
 * Note this covers whole bundles, not individual files. A file added to an
 * upstream bundle is a change to that bundle and keeps its header.
 */

$header = <<<'EOF'
This file is part of Augias project.

(c) HERC SI <opensource@herc-si.fr>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src/AccountingBundle',
        __DIR__ . '/src/BillBundle',
        __DIR__ . '/src/CatalogBundle',
        __DIR__ . '/src/ElectronicInvoicingBundle',
        __DIR__ . '/src/SupplierBundle',
        // ecs.php lints itself and rector.php but knows nothing about this
        // file, so it lints itself here — with its own header, which is the
        // right one for it.
        __FILE__,
    ])
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'comment_type' => 'comment',
        'header' => trim($header),
        'location' => 'after_declare_strict',
        'separate' => 'both',
    ]);
