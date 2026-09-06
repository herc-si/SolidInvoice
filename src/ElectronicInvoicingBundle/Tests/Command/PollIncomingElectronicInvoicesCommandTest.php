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

namespace Augias\ElectronicInvoicingBundle\Tests\Command;

use Augias\CoreBundle\Test\Traits\ConsoleTesterTrait;
use Augias\ElectronicInvoicingBundle\Command\PollIncomingElectronicInvoicesCommand;
use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;
use Augias\ElectronicInvoicingBundle\Repository\ElectronicInvoiceReceiptRepository;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\Constraint\CommandIsSuccessful;
use Symfony\Component\Filesystem\Filesystem;
use function rewind;
use function str_replace;
use function stream_get_contents;

#[Group('functional')]
#[CoversClass(PollIncomingElectronicInvoicesCommand::class)]
final class PollIncomingElectronicInvoicesCommandTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use ConsoleTesterTrait;

    protected function tearDown(): void
    {
        new Filesystem()->remove(
            self::getContainer()->getParameter('kernel.project_dir') . '/var/einvoicing/incoming/' . $this->company->getId()->toBase58(),
        );

        parent::tearDown();
    }

    public function testCommandImportsFromEveryCompanyWithAnActiveReceivingProvider(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $setting = new ElectronicInvoiceProviderSetting();
        $setting->setCompany($this->company)
            ->setName('Test Provider')
            ->setProvider('test_provider')
            ->setSettings([])
            ->setActive(true);
        $entityManager->persist($setting);
        $entityManager->flush();

        $output = $this->runTestCommand();

        self::assertStringContainsString('Imported 1 invoice(s) across 1 compan(y/ies). Errors: 0', $output);

        $repository = self::getContainer()->get(ElectronicInvoiceReceiptRepository::class);
        self::assertCount(1, $repository->findAll());
    }

    public function testCommandImportsNothingWhenNoCompanyHasAnActiveReceivingProvider(): void
    {
        $output = $this->runTestCommand();

        self::assertStringContainsString('Imported 0 invoice(s) across 0 compan(y/ies). Errors: 0', $output);
    }

    private function runTestCommand(): string
    {
        $application = new Application(self::$kernel);

        /** @var LazyCommand $lazyCommand */
        $lazyCommand = $application->find('augias:einvoicing:poll-incoming-invoices');

        /** @var PollIncomingElectronicInvoicesCommand $command */
        $command = $lazyCommand->getCommand();
        $this->initOutput([]);
        $this->input = new ArrayInput([]);
        $this->input->setStream(self::createStream([]));

        $command->setIo(new IO($this->input, $this->output));

        $this->statusCode = $command->run($this->input, $this->output);

        Assert::assertThat($this->statusCode, new CommandIsSuccessful());

        rewind($this->output->getStream());

        $display = stream_get_contents($this->output->getStream());

        return str_replace(\PHP_EOL, "\n", $display);
    }
}
