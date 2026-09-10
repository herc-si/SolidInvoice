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

namespace Augias\ElectronicInvoicingBundle\Dashboard;

use Augias\DashboardBundle\Attribute\AsDashboardWidget;
use Augias\DashboardBundle\Enum\WidgetWidth;
use Augias\DashboardBundle\Enum\WidgetZone;
use Augias\DashboardBundle\Widgets\WidgetInterface;
use Augias\ElectronicInvoicingBundle\Provider\ElectronicInvoiceProviderRegistry;
use Augias\ElectronicInvoicingBundle\Repository\ElectronicInvoiceSubmissionRepository;
use Augias\ElectronicInvoicingBundle\Twig\ElectronicInvoiceExtension;

/**
 * How the invoices this company sent electronically are getting on.
 *
 * Sending is not delivering. A submission the platform accepted, one it is
 * still chewing on and one it refused outright all look identical from the
 * invoice list, and only the last needs the user — but nothing surfaces it, so
 * a rejected invoice can sit unnoticed while the company believes it was sent.
 *
 * The status shown is Augias's own normalisation of the provider's vocabulary,
 * resolved through the same extension the rest of the UI uses, so a submission
 * cannot read Accepted here and Pending on its own page.
 *
 * @see \Augias\ElectronicInvoicingBundle\Tests\Dashboard\OutgoingInvoicesWidgetTest
 */
#[AsDashboardWidget(
    id: 'einvoicing_outgoing',
    label: 'dashboard.widget.einvoicing_outgoing',
    icon: 'tabler:send',
    zone: WidgetZone::RightColumn,
    // Below the inbox: what arrived usually needs doing, what left usually does
    // not.
    priority: 105,
    width: WidgetWidth::Full,
)]
final readonly class OutgoingInvoicesWidget implements WidgetInterface
{
    private const int ROWS_SHOWN = 5;

    public function __construct(
        private ElectronicInvoiceProviderRegistry $registry,
        private ElectronicInvoiceSubmissionRepository $submissions,
        private ElectronicInvoiceExtension $statusResolver,
    ) {
    }

    public function supports(): bool
    {
        return $this->registry->hasActiveProvider();
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $submissions = $this->submissions->findRecent(self::ROWS_SHOWN);
        $statuses = [];

        // Resolved here rather than in Twig: the resolver reaches for the
        // provider, and doing that from a template would put a service lookup
        // inside a loop with no way to see it.
        foreach ($submissions as $submission) {
            $statuses[(string) $submission->getId()] = $this->statusResolver->resolveProcessingStatus($submission);
        }

        return [
            'submissions' => $submissions,
            'statuses' => $statuses,
            'failedTotal' => $this->submissions->countFailed(),
            'hasSubmissions' => [] !== $submissions,
        ];
    }

    public function getTemplate(): string
    {
        return '@AugiasElectronicInvoicing/Widget/outgoing.html.twig';
    }
}
