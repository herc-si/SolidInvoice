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

namespace Augias\QuoteBundle\Action;

use Augias\QuoteBundle\Enum\QuoteStatus;
use Augias\QuoteBundle\Repository\QuoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;

final readonly class Index
{
    public function __construct(
        private QuoteRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[Template('@AugiasQuote/Default/index.html.twig')]
    public function __invoke(Request $request): array
    {
        $isArchived = $request->query->get('archived', '0') === '1';

        // Get quote counts by status
        $pendingCount = $this->repository->getTotalQuotes(QuoteStatus::Pending);
        $acceptedCount = $this->repository->getTotalQuotes(QuoteStatus::Accepted);
        $cancelledCount = $this->repository->getTotalQuotes(QuoteStatus::Cancelled);
        $draftCount = $this->repository->getTotalQuotes(QuoteStatus::Draft);
        $declinedCount = $this->repository->getTotalQuotes(QuoteStatus::Declined);

        // Calculate total active quotes
        $totalActiveQuotes = $pendingCount + $acceptedCount + $cancelledCount + $draftCount + $declinedCount;

        // Get archived quotes count (need to temporarily disable the filter)
        $filters = $this->entityManager->getFilters();
        $filters->disable('archivable');
        try {
            $totalArchivedQuotes = $this->repository->count(['archived' => true]);
        } finally {
            $filters->enable('archivable');
        }

        return [
            'isArchived' => $isArchived,
            'totalActiveQuotes' => $totalActiveQuotes,
            'totalArchivedQuotes' => $totalArchivedQuotes,
            'pendingCount' => $pendingCount,
            'acceptedCount' => $acceptedCount,
            'declinedCount' => $declinedCount,
            'draftCount' => $draftCount,
            'status_list_count' => [
                QuoteStatus::Pending->value => $pendingCount,
                QuoteStatus::Accepted->value => $acceptedCount,
                QuoteStatus::Cancelled->value => $cancelledCount,
                QuoteStatus::Draft->value => $draftCount,
                QuoteStatus::Declined->value => $declinedCount,
            ],
        ];
    }
}
