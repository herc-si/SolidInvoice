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

namespace Augias\AccountingBundle\Command;

use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Service\LedgerChainVerifier;
use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use function assert;
use function sprintf;

/**
 * Re-walks every sealed book and reports whether it still hashes to what it was
 * sealed as.
 *
 * A command rather than only a screen, because this is the check somebody else
 * may need to run: an accountant, an auditor, or the user proving to themselves
 * that a backup they restored is intact. It reads and reports; it never repairs,
 * since silently rewriting a hash is precisely the act the chain exists to make
 * impossible.
 *
 * @see \Augias\AccountingBundle\Tests\Command\VerifyLedgerCommandTest
 */
#[AsCommand(
    name: 'augias:accounting:verify-ledger',
    description: 'Verify the hash chain of every sealed accounting ledger',
)]
final class VerifyLedgerCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly CompanyRepository $companyRepository,
        private readonly LedgerChainVerifier $verifier,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(LedgerEntry::class);
        assert($entityManager instanceof EntityManagerInterface);

        // Every company's books, not the active tenant's: run from cron or a
        // shell there is no active tenant, and an installation-wide answer is
        // the useful one anyway.
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        $rows = [];
        $failed = false;

        try {
            /** @var list<Company> $companies */
            $companies = $this->companyRepository->findAll();

            foreach ($companies as $company) {
                foreach (LedgerBook::cases() as $book) {
                    $verification = $this->verifier->verify($company, $book);

                    if (0 === $verification->entriesChecked) {
                        continue;
                    }

                    $failure = $verification->firstFailure();
                    $failed = $failed || ! $verification->isValid();

                    $rows[] = [
                        (string) $company->getName(),
                        $book->value,
                        (string) $verification->entriesChecked,
                        $verification->isValid()
                            ? 'OK'
                            : sprintf(
                                'BROKEN (%s at entry #%s)',
                                $failure['reason'] ?? 'unknown',
                                $failure['sequenceNumber'] ?? '?',
                            ),
                    ];
                }
            }
        } finally {
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        if ([] === $rows) {
            $this->io->note('No sealed ledger entries to verify — nothing has been closed yet.');

            return self::SUCCESS;
        }

        $this->io->table(['Company', 'Book', 'Entries', 'Result'], $rows);

        if ($failed) {
            $this->io->error('At least one ledger no longer matches what it was sealed as.');

            return self::FAILURE;
        }

        $this->io->success('Every sealed ledger verifies.');

        return self::SUCCESS;
    }
}
