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

namespace Augias\DashboardBundle\Layout;

use Override;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Loads the current user's layout and resolves it, once per request.
 *
 * The memoisation is not a micro-optimisation: resolving calls supports() on
 * every registered widget, and each of the three zones asks for the layout
 * separately while rendering. Without it the page would run those checks three
 * times over.
 *
 * @see \Augias\DashboardBundle\Tests\Layout\LayoutProviderTest
 */
final class LayoutProvider implements LayoutProviderInterface, ResetInterface
{
    private ?ResolvedLayout $layout = null;

    public function __construct(
        private readonly DashboardLayoutManager $layoutManager,
        private readonly LayoutResolver $resolver,
    ) {
    }

    #[Override]
    public function currentLayout(): ResolvedLayout
    {
        return $this->layout ??= $this->resolver->resolve($this->layoutManager->load());
    }

    /**
     * Under a worker runtime this service outlives the request, and one user's
     * layout would otherwise be rendered for the next.
     */
    #[Override]
    public function reset(): void
    {
        $this->layout = null;
    }
}
