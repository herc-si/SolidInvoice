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

namespace Augias\ElectronicInvoicingBundle\Command;

use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Repository\CompanyRepository;
use Augias\ElectronicInvoicingBundle\Manager\ElectronicInvoiceReceiptManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Throwable;
use function assert;
use function count;
use function sprintf;

/**
 * Imports invoices received by each company since the last run, for whichever
 * companies have an active provider that supports receiving (see
 * {@see ElectronicInvoiceReceiptManagerInterface::isReceivingEnabled()}) —
 * provider-agnostic, unlike {@see PollSuperPdpInvoiceStatusCommand}, since
 * receiving is a generic capability any provider can opt into, not a SUPER
 * PDP-specific status-polling workaround.
 *
 * @see \Augias\ElectronicInvoicingBundle\Tests\Command\PollIncomingElectronicInvoicesCommandTest
 */
#[AsCommand(
    name: 'solidinvoice:einvoicing:poll-incoming-invoices',
    description: 'Import electronic invoices received by each company since the last run',
)]
#[AsCronTask('#hourly', schedule: 'poll_incoming_electronic_invoices')]
final class PollIncomingElectronicInvoicesCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly CompanyRepository $companyRepository,
        private readonly ElectronicInvoiceReceiptManagerInterface $receiptManager,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(Company::class);
        assert($entityManager instanceof EntityManagerInterface);

        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        $imported = 0;
        $companiesWithNewInvoices = 0;
        $errors = 0;

        try {
            foreach ($this->companyRepository->findAll() as $company) {
                if (! $this->receiptManager->isReceivingEnabled($company)) {
                    continue;
                }

                try {
                    $receipts = $this->receiptManager->importNew($company);

                    if ($receipts !== []) {
                        ++$companiesWithNewInvoices;
                        $imported += count($receipts);
                    }
                } catch (Throwable $e) {
                    ++$errors;
                    $this->io->error(sprintf('Could not import incoming invoices for company %s: %s', (string) $company->getId(), $e->getMessage()));
                }
            }
        } finally {
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        $this->io->success(sprintf(
            'Imported %d invoice(s) across %d compan(y/ies). Errors: %d',
            $imported,
            $companiesWithNewInvoices,
            $errors,
        ));

        return self::SUCCESS;
    }
}
