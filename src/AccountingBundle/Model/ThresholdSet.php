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

namespace Augias\AccountingBundle\Model;

use Augias\AccountingBundle\Enum\ActivityNature;
use Countable;
use IteratorAggregate;
use Traversable;
use function array_filter;
use function array_values;
use function count;

/**
 * Every limit that applies to a company on a given date, keyed by
 * {@see Threshold::$key}. A regime with no limits at all returns an empty set
 * rather than null, so callers never have to branch on that.
 *
 * @implements IteratorAggregate<int, Threshold>
 */
final readonly class ThresholdSet implements Countable, IteratorAggregate
{
    /** @var array<string, Threshold> */
    private array $thresholds;

    /**
     * @param list<Threshold> $thresholds
     */
    public function __construct(array $thresholds = [])
    {
        $keyed = [];

        foreach ($thresholds as $threshold) {
            $keyed[$threshold->key] = $threshold;
        }

        $this->thresholds = $keyed;
    }

    public function get(string $key): ?Threshold
    {
        return $this->thresholds[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->thresholds[$key]);
    }

    /**
     * @return list<Threshold>
     */
    public function all(): array
    {
        return array_values($this->thresholds);
    }

    /**
     * Limits that apply to a given activity, including the ones that span every
     * activity (nature `null`).
     *
     * @return list<Threshold>
     */
    public function forNature(ActivityNature $nature): array
    {
        return array_values(array_filter(
            $this->thresholds,
            static fn (Threshold $threshold): bool => $threshold->nature === $nature || null === $threshold->nature,
        ));
    }

    public function getIterator(): Traversable
    {
        yield from array_values($this->thresholds);
    }

    public function count(): int
    {
        return count($this->thresholds);
    }

    public function isEmpty(): bool
    {
        return [] === $this->thresholds;
    }
}
