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

namespace Augias\CoreBundle\Export\Action;

use Augias\CoreBundle\Entity\ExportJob;
use Augias\CoreBundle\Export\Enum\ExportFormat;
use Augias\CoreBundle\Repository\ExportJobRepository;
use Augias\UserBundle\Entity\User;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final readonly class ListExports
{
    public function __construct(
        private ExportJobRepository $exportJobRepository,
    ) {
    }

    /**
     * @return array{jobs: list<ExportJob>, formats: list<ExportFormat>}
     */
    #[Template('@AugiasCore/Export/list.html.twig')]
    public function __invoke(?UserInterface $user): array
    {
        if (! $user instanceof User) {
            throw new AccessDeniedException();
        }

        return [
            'jobs' => $this->exportJobRepository->findForUser($user->getId()),
            'formats' => ExportFormat::cases(),
        ];
    }
}
