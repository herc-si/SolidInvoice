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

namespace Augias\CoreBundle\Company;

use Augias\CoreBundle\Repository\CompanyRepository;
use Override;
use SolidWorx\Platform\PlatformBundle\Feature\SubscribableInterface;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;
use Symfony\Component\Uid\Ulid;

/**
 * @see \Augias\CoreBundle\Tests\Company\CompanySubscriberResolverTest
 */
final readonly class CompanySubscriberResolver implements SubscriberResolver
{
    public function __construct(
        private CompanySelectorInterface $selector,
        private CompanyRepository $repository,
    ) {
    }

    #[Override]
    public function resolve(): ?SubscribableInterface
    {
        $id = $this->selector->getCompany();

        return $id instanceof Ulid ? $this->repository->find($id) : null;
    }
}
