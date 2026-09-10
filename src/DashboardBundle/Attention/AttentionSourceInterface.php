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

namespace Augias\DashboardBundle\Attention;

/**
 * An extra section for the "Attention Required" card, contributed by another
 * bundle.
 *
 * The card is where a user looks to find out what is waiting for them, so a new
 * kind of waiting thing belongs in it rather than in a card of its own. But the
 * dashboard has no business knowing what a tax regime or a turnover ceiling is,
 * so a source hands over a template and its data rather than rows in a shape
 * this bundle had to invent. The four sections that ship here keep their own
 * markup untouched; a source's template simply renders alongside them using the
 * same `.attention-section` classes.
 *
 * Implementations are tagged by interface — the same mechanism
 * {@see \Augias\DashboardBundle\Checklist\ChecklistItemInterface} uses.
 *
 * @see \Augias\DashboardBundle\Tests\Widgets\AttentionRequiredWidgetTest
 */
interface AttentionSourceInterface
{
    /**
     * Whether this source applies at all — a feature switched off, a regime
     * never chosen. Checked before {@see hasItems()}, so a source that cannot
     * apply costs no queries.
     */
    public function supports(): bool;

    /**
     * Whether there is anything to show right now.
     *
     * Separate from {@see getData()} because the card's empty state depends on
     * it: "All caught up" must not be printed above a section that has rows,
     * and an empty section must not stop it being printed.
     */
    public function hasItems(): bool;

    public function getTemplate(): string;

    /**
     * @return array<string, mixed>
     */
    public function getData(): array;
}
