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

namespace Augias\ClientBundle\Action;

use Augias\ClientBundle\Entity\Contact;
use Augias\ClientBundle\Enum\ClientStatus;
use Augias\ClientBundle\Repository\ClientRepository;
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;

final readonly class Index
{
    public function __construct(
        private ClientRepository $clientRepository,
        private InvoiceRepository $invoiceRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[Template('@AugiasClient/Default/index.html.twig')]
    public function __invoke(Request $request): array
    {
        $isArchived = $request->query->get('archived', '0') === '1';

        // Get client counts
        $totalActiveClients = $this->clientRepository->getTotalClients(ClientStatus::Active);

        // Get archived clients count (need to temporarily disable the filter)
        $filters = $this->entityManager->getFilters();
        $filters->disable('archivable');

        $totalArchivedClients = $this->clientRepository->getTotalClients(ClientStatus::Archived);
        $totalClients = $this->clientRepository->getTotalClients();
        $filters->enable('archivable');

        // Get total contacts count
        $totalContacts = $this->entityManager->getRepository(Contact::class)->count([]);

        // Get outstanding amounts by currency
        $totalOutstanding = $this->invoiceRepository->getTotalOutstandingByCurrency();

        return [
            'isArchived' => $isArchived,
            'totalActiveClients' => $totalActiveClients,
            'totalArchivedClients' => $totalArchivedClients,
            'totalClients' => $totalClients,
            'totalContacts' => $totalContacts,
            'totalOutstanding' => $totalOutstanding,
        ];
    }
}
