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
use Augias\AccountingBundle\Model\RaisedThreshold;
use Augias\AccountingBundle\Notification\ThresholdReachedNotification;
use Augias\AccountingBundle\Service\ThresholdMonitor;
use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Repository\CompanyRepository;
use Augias\NotificationBundle\Notification\NotificationManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Throwable;
use function assert;
use function count;
use function sprintf;

/**
 * Compares each company's year-to-date turnover with the limits its regime sets
 * and alerts on anything newly crossed.
 *
 * Daily, because the consequence of a crossing starts on the day it happens:
 * passing a VAT threshold makes a micro-entrepreneur liable for VAT from that
 * point, and every invoice raised afterwards in ignorance has to be reissued.
 * Finding out at the next declaration would be finding out too late.
 *
 * The alert row is written by {@see ThresholdMonitor} and is what stops the
 * same milestone being reported every morning; sending the email is done here,
 * and a transport failure is logged rather than retried — the crossing is
 * recorded either way, and a retry that duplicated the row would be worse than
 * a missed email.
 *
 * @see \Augias\AccountingBundle\Tests\Command\CheckTurnoverThresholdsCommandTest
 */
#[AsCommand(
    name: 'augias:accounting:check-thresholds',
    description: 'Alert on turnover approaching or passing the limits set by each company\'s tax regime',
)]
#[AsCronTask('#daily', schedule: 'accounting_threshold_check')]
final class CheckTurnoverThresholdsCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly CompanyRepository $companyRepository,
        private readonly ThresholdMonitor $monitor,
        private readonly NotificationManager $notificationManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(LedgerEntry::class);
        assert($entityManager instanceof EntityManagerInterface);

        // Every tenant: run from cron there is no active company, and the
        // monitor is given the one it is working on explicitly.
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        $raisedCount = 0;

        try {
            /** @var list<Company> $companies */
            $companies = $this->companyRepository->findAll();

            foreach ($companies as $company) {
                $raised = $this->monitor->check($company);
                $raisedCount += count($raised);

                foreach ($raised as $raisedThreshold) {
                    $this->announce($raisedThreshold);
                }
            }

            $entityManager->flush();
        } finally {
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        $this->io->success(sprintf('Raised %d threshold alert(s).', $raisedCount));

        return self::SUCCESS;
    }

    private function announce(RaisedThreshold $raised): void
    {
        $this->io->warning(sprintf(
            '%s reached %d%% of %s',
            (string) $raised->alert->getCompany()->getName(),
            $raised->alert->getStep(),
            $raised->threshold->key,
        ));

        try {
            $this->notificationManager->sendNotification(
                new ThresholdReachedNotification([
                    'alert' => $raised->alert,
                    'threshold' => $raised->threshold->labelKey,
                ])
            );

            $raised->alert->setNotifiedAt(new DateTimeImmutable());
        } catch (Throwable $e) {
            // Left with no notifiedAt, which is the record that the alert was
            // raised but never announced. Not retried: the row already exists,
            // so a retry could only duplicate the email, not the alert.
            $this->logger->error('Failed to send accounting threshold notification', [
                'threshold' => $raised->threshold->key,
                'company' => (string) $raised->alert->getCompany()->getId(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
