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

namespace SolidInvoice\ElectronicInvoicingBundle\Tests\Command;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\ConsoleTesterTrait;
use SolidInvoice\ElectronicInvoicingBundle\Command\PollSuperPdpInvoiceStatusCommand;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;
use SolidInvoice\ElectronicInvoicingBundle\Entity\ElectronicInvoiceSubmission;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\Constraint\CommandIsSuccessful;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function json_encode;
use function rewind;
use function str_replace;
use function stream_get_contents;

#[Group('functional')]
#[CoversClass(PollSuperPdpInvoiceStatusCommand::class)]
final class PollSuperPdpInvoiceStatusCommandTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use ConsoleTesterTrait;

    public function testCommandRefreshesTheStatusOfAPendingSubmission(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $setting = new ElectronicInvoiceProviderSetting();
        $setting->setCompany($this->company)
            ->setName('SUPER PDP')
            ->setProvider('super_pdp')
            ->setSettings(['client_id' => 'id', 'client_secret' => 'secret'])
            ->setActive(true);
        $entityManager->persist($setting);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $invoice = InvoiceFactory::createOne(['company' => $this->company, 'client' => $client]);

        $submission = new ElectronicInvoiceSubmission();
        $submission->setCompany($this->company)
            ->setInvoice($invoice)
            ->setProvider('super_pdp')
            ->setSuccess(true)
            ->setExternalReference('4242');
        $entityManager->persist($submission);
        $entityManager->flush();

        self::getContainer()->set(HttpClientInterface::class, new MockHttpClient([
            static fn (): MockResponse => new MockResponse((string) json_encode(['access_token' => 'a-token', 'expires_in' => 3600])),
            static fn (): MockResponse => new MockResponse((string) json_encode([
                'id' => 4242,
                'events' => [
                    ['status_code' => 'api:uploaded', 'created_at' => '2026-01-01T10:00:00Z'],
                    ['status_code' => 'fr:205', 'created_at' => '2026-01-02T10:00:00Z'],
                ],
            ])),
        ]));

        $output = $this->runTestCommand();

        self::assertStringContainsString('Refreshed 1 submission(s). Errors: 0', $output);

        $entityManager->clear();
        $repository = self::getContainer()->get('doctrine')->getRepository(ElectronicInvoiceSubmission::class);
        $refreshed = $repository->find($submission->getId());

        self::assertSame('fr:205', $refreshed?->getStatusCode());
    }

    private function runTestCommand(): string
    {
        // Reuse the already-booted kernel (from EnsureApplicationInstalled) instead of
        // calling self::bootKernel() again, which would create a fresh container and
        // discard the MockHttpClient set on the current one.
        $application = new Application(self::$kernel);

        /** @var LazyCommand $lazyCommand */
        $lazyCommand = $application->find('solidinvoice:einvoicing:poll-super-pdp-status');

        /** @var PollSuperPdpInvoiceStatusCommand $command */
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
