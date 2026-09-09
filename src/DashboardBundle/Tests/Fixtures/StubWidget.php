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

use Augias\DashboardBundle\Widgets\WidgetInterface;
use Throwable;

/**
 * A widget with no dependencies, so layout tests can be about layout.
 */
final class StubWidget implements WidgetInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string $template = 'stub.html.twig',
        private readonly array $data = [],
        private readonly bool $supported = true,
        private readonly ?Throwable $supportsFailure = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function supports(): bool
    {
        if (null !== $this->supportsFailure) {
            throw $this->supportsFailure;
        }

        return $this->supported;
    }
}
