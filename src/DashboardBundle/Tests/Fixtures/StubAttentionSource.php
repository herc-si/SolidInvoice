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

namespace Augias\DashboardBundle\Tests\Fixtures;

use Augias\DashboardBundle\Attention\AttentionSourceInterface;
use Throwable;

/**
 * A contributor with no dependencies, so the folding-in can be tested without a
 * second bundle.
 */
final readonly class StubAttentionSource implements AttentionSourceInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private bool $supported = true,
        private bool $hasItems = true,
        private string $template = 'stub_section.html.twig',
        private array $data = [],
        private ?Throwable $failure = null,
    ) {
    }

    public function supports(): bool
    {
        if (null !== $this->failure) {
            throw $this->failure;
        }

        return $this->supported;
    }

    public function hasItems(): bool
    {
        return $this->hasItems;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
