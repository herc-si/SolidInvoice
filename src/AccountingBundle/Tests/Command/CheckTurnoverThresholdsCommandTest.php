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

namespace Augias\AccountingBundle\Tests\Command;

use const PHP_EOL;
use Augias\AccountingBundle\AccountingSettings;
use Augias\AccountingBundle\Command\CheckTurnoverThresholdsCommand;
use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Regime\Fr\MicroEntrepriseRegime;
use Augias\AccountingBundle\Repository\ThresholdAlertRepository;
use Augias\CoreBundle\Test\Traits\ConsoleTesterTrait;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\SettingsBundle\SystemConfig;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use function rewind;
use function str_replace;
use function stream_get_contents;

#[CoversClass(CheckTurnoverThresholdsCommand::class)]
final class CheckTurnoverThresholdsCommandTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use ConsoleTesterTrait;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $this->entityManager = $entityManager;

        $config = self::getContainer()->get(SystemConfig::class);
        $config->set(SystemConfig::CURRENCY_CONFIG_PATH, 'EUR');
        $config->set(AccountingSettings::REGIME, MicroEntrepriseRegime::CODE);
        $config->set(AccountingSettings::PRIMARY_ACTIVITY, ActivityNature::ServicesBnc->value);
    }

    public function testItRaisesNothingForABusinessWellWithinItsLimits(): void
    {
        $this->revenue(1_000_00);

        $output = $this->runTestCommand();

        self::assertStringContainsString('Raised 0 threshold alert(s)', $output);
        self::assertSame(Command::SUCCESS, $this->statusCode);
    }

    public function testItRaisesAndRecordsAnAlertWhenALimitIsPassed(): void
    {
        // Comfortably past both VAT thresholds for services.
        $this->revenue(50_000_00);

        $output = $this->runTestCommand();

        self::assertStringContainsString('vat_franchise', $output);
        self::assertSame(Command::SUCCESS, $this->statusCode);

        $alerts = self::getContainer()->get(ThresholdAlertRepository::class)
            ->findForYear($this->company, (int) new DateTimeImmutable('today')->format('Y'));

        self::assertNotSame([], $alerts);
    }

    /**
     * The job runs every day. A milestone already reported is not reported
     * again, which is the whole reason the alert rows exist.
     */
    public function testRunningItAgainReportsNothingNew(): void
    {
        $this->revenue(50_000_00);

        $this->runTestCommand();
        $output = $this->runTestCommand();

        self::assertStringContainsString('Raised 0 threshold alert(s)', $output);
    }

    private function revenue(int $amount): void
    {
        // Dated today: the limits are annual and cumulative, so the alert has
        // to be reachable from the date the command actually runs on.
        $entry = new LedgerEntry()
            ->setBook(LedgerBook::Revenue)
            ->setEntryDate(new DateTimeImmutable('today'))
            ->setLabel('Invoice payment')
            ->setCounterpartyName('Johnston PLC')
            ->setAmount(BigInteger::of($amount))
            ->setCurrencyCode('EUR')
            ->setActivityNature(ActivityNature::ServicesBnc);

        $entry->setCompany($this->company);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    private function runTestCommand(): string
    {
        $application = new Application(self::bootKernel());

        /** @var LazyCommand $lazyCommand */
        $lazyCommand = $application->find('augias:accounting:check-thresholds');

        /** @var CheckTurnoverThresholdsCommand $command */
        $command = $lazyCommand->getCommand();

        $this->initOutput([]);
        $this->input = new ArrayInput([]);
        $this->input->setStream(self::createStream([]));

        $command->setIo(new IO($this->input, $this->output));

        $this->statusCode = $command->run($this->input, $this->output);

        rewind($this->output->getStream());

        return str_replace(PHP_EOL, "\n", (string) stream_get_contents($this->output->getStream()));
    }
}
