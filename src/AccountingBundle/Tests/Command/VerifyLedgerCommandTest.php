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
use Augias\AccountingBundle\Command\VerifyLedgerCommand;
use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Service\AccountingPeriodManager;
use Augias\CoreBundle\Test\Traits\ConsoleTesterTrait;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
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

#[CoversClass(VerifyLedgerCommand::class)]
final class VerifyLedgerCommandTest extends KernelTestCase
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
    }

    public function testItSaysSoWhenNothingHasBeenSealedYet(): void
    {
        $output = $this->runTestCommand();

        self::assertStringContainsString('nothing has been closed yet', $output);
        self::assertSame(Command::SUCCESS, $this->statusCode);
    }

    public function testASealedBookVerifies(): void
    {
        $this->sealAnEntry();

        $output = $this->runTestCommand();

        self::assertStringContainsString('OK', $output);
        self::assertStringContainsString('Every sealed ledger verifies', $output);
        self::assertSame(Command::SUCCESS, $this->statusCode);
    }

    /**
     * The whole point of the chain: a figure changed underneath the application
     * is reported, and the command fails rather than passing quietly.
     */
    public function testATamperedBookIsReportedAndFailsTheCommand(): void
    {
        $this->sealAnEntry();

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE accounting_ledger_entries SET amount = 999',
        );
        $this->entityManager->clear();

        $output = $this->runTestCommand();

        self::assertStringContainsString('BROKEN (altered at entry #1)', $output);
        self::assertStringContainsString('no longer matches what it was sealed as', $output);
        self::assertSame(Command::FAILURE, $this->statusCode);
    }

    private function sealAnEntry(): void
    {
        $manager = self::getContainer()->get(AccountingPeriodManager::class);

        $entry = new LedgerEntry()
            ->setBook(LedgerBook::Revenue)
            ->setEntryDate(new DateTimeImmutable('2026-01-15'))
            ->setLabel('Invoice payment')
            ->setCounterpartyName('Johnston PLC')
            ->setDocumentReference('INV-001')
            ->setAmount(BigInteger::of(120_000))
            ->setCurrencyCode('EUR')
            ->setActivityNature(ActivityNature::ServicesBnc);

        $entry->setCompany($this->company);

        $manager->assignPeriod($entry, PeriodType::Quarter);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        $period = $entry->getPeriod();
        self::assertInstanceOf(AccountingPeriod::class, $period);

        $manager->close($period);
    }

    private function runTestCommand(): string
    {
        $application = new Application(self::bootKernel());

        /** @var LazyCommand $lazyCommand */
        $lazyCommand = $application->find('augias:accounting:verify-ledger');

        /** @var VerifyLedgerCommand $command */
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
