<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ElectronicInvoicingBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceSubmission;
use SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdp\SuperPdpApiException;
use SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdp\SuperPdpClient;
use SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdpProvider;
use SolidInvoice\ElectronicInvoicingBundle\Repository\ElectronicInvoiceProviderSettingRepository;
use SolidInvoice\ElectronicInvoicingBundle\Repository\ElectronicInvoiceSubmissionRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use function array_key_last;
use function assert;
use function is_array;
use function is_string;
use function sprintf;
use function usort;

/**
 * SUPER PDP exposes no webhooks (see
 * https://api.superpdp.tech/openapi/superpdp.json) — only polling via
 * `GET /v1.beta/invoices/{id}` — so this periodically refreshes the last
 * known `status_code` on successful {@see ElectronicInvoiceSubmission}
 * records until they reach a terminal status.
 *
 * @see \SolidInvoice\ElectronicInvoicingBundle\Tests\Command\PollSuperPdpInvoiceStatusCommandTest
 */
#[AsCommand(
    name: 'solidinvoice:einvoicing:poll-super-pdp-status',
    description: 'Refresh the processing status of invoices submitted to SUPER PDP',
)]
#[AsCronTask('#hourly', schedule: 'poll_super_pdp_invoice_status')]
final class PollSuperPdpInvoiceStatusCommand extends Command
{
    /**
     * `fr:*` codes per https://api.superpdp.tech/openapi/superpdp.json,
     * plus the `api:*` codes that mean SUPER PDP itself will not process the
     * invoice any further.
     */
    private const array TERMINAL_STATUS_CODES = [
        'fr:205', 'fr:206', 'fr:209', 'fr:210', 'fr:213', 'fr:501',
        'api:rejected', 'api:invalid',
    ];

    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly ElectronicInvoiceSubmissionRepository $submissionRepository,
        private readonly ElectronicInvoiceProviderSettingRepository $settingRepository,
        private readonly SuperPdpClient $client,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(ElectronicInvoiceSubmission::class);
        assert($entityManager instanceof EntityManagerInterface);

        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        $updated = 0;
        $errors = 0;

        try {
            $submissions = $this->submissionRepository->findPendingByProvider(SuperPdpProvider::getName(), self::TERMINAL_STATUS_CODES);

            foreach ($submissions as $submission) {
                try {
                    if ($this->refreshStatus($submission)) {
                        ++$updated;
                    }
                } catch (SuperPdpApiException $e) {
                    ++$errors;
                    $this->io->error(sprintf('Could not refresh status for submission %s: %s', (string) $submission->getId(), $e->getMessage()));
                }
            }

            $entityManager->flush();
        } finally {
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        $this->io->success(sprintf('Refreshed %d submission(s). Errors: %d', $updated, $errors));

        return self::SUCCESS;
    }

    /**
     * @throws SuperPdpApiException
     */
    private function refreshStatus(ElectronicInvoiceSubmission $submission): bool
    {
        $externalReference = $submission->getExternalReference();

        if ($externalReference === null || $externalReference === '') {
            return false;
        }

        $setting = $this->settingRepository->findActiveForCompany($submission->getCompany()->getId(), SuperPdpProvider::getName());

        if (! $setting instanceof ElectronicInvoiceProviderSetting) {
            return false;
        }

        $settings = $setting->getSettings();
        $clientId = $settings['client_id'] ?? null;
        $clientSecret = $settings['client_secret'] ?? null;

        if (! is_string($clientId) || ! is_string($clientSecret)) {
            return false;
        }

        $accessToken = $this->client->getAccessToken($clientId, $clientSecret);
        $invoice = $this->client->getInvoice($accessToken, $externalReference);

        $statusCode = $this->latestStatusCode($invoice);

        if ($statusCode === null || $statusCode === $submission->getStatusCode()) {
            return false;
        }

        $submission->setStatusCode($statusCode);

        return true;
    }

    /**
     * @param array<string, mixed> $invoice
     */
    private function latestStatusCode(array $invoice): ?string
    {
        $events = $invoice['events'] ?? null;

        if (! is_array($events) || $events === []) {
            return null;
        }

        usort($events, static fn (mixed $a, mixed $b): int => ($a['created_at'] ?? '') <=> ($b['created_at'] ?? ''));

        $latest = $events[array_key_last($events)];
        $statusCode = is_array($latest) ? $latest['status_code'] ?? null : null;

        return is_string($statusCode) ? $statusCode : null;
    }
}
