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

namespace Augias\InvoiceBundle\Tests\Listener\Mailer;

use Augias\CoreBundle\Contracts\PaidSubscriptionGateInterface;
use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Templates\BillingDocumentType;
use Augias\CoreBundle\Templates\BillingTemplateRegistry;
use Augias\CoreBundle\Templates\BillingTemplateResolver;
use Augias\InvoiceBundle\Email\InvoiceEmail;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Listener\Mailer\InvoiceEmailTemplateListener;
use Augias\SaasBundle\Feature\Feature;
use Augias\SettingsBundle\SystemConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use function dirname;
use function sys_get_temp_dir;
use function uniqid;

final class InvoiceEmailTemplateListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private string $invoiceDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->invoiceDir = sys_get_temp_dir() . '/' . uniqid('invoice_email_tpl_', true) . '/invoice';
        $this->filesystem->dumpFile($this->invoiceDir . '/sleek/email.html.twig', 'sleek');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(dirname($this->invoiceDir));
    }

    public function testSwapsTheEmailTemplateWhenACustomTemplateIsSelected(): void
    {
        $invoice = new Invoice();
        $invoice->setCompany(new Company());

        $listener = new InvoiceEmailTemplateListener($this->createResolver($invoice->getCompany(), 'sleek'));

        $message = new InvoiceEmail($invoice);
        $listener(new MessageEvent($message, Envelope::create($message->to('test@example.com')->from('from@example.com')), 'smtp'));

        self::assertSame('@AugiasInvoice/Templates/sleek/email.html.twig', $message->getHtmlTemplate());
    }

    public function testKeepsTheDefaultTemplateWhenNoCustomTemplateApplies(): void
    {
        $invoice = new Invoice();
        $invoice->setCompany(new Company());

        $listener = new InvoiceEmailTemplateListener($this->createResolver($invoice->getCompany(), null));

        $message = new InvoiceEmail($invoice);
        $listener(new MessageEvent($message, Envelope::create($message->to('test@example.com')->from('from@example.com')), 'smtp'));

        self::assertSame('@AugiasInvoice/Email/invoice.html.twig', $message->getHtmlTemplate());
    }

    public function testIgnoresOtherMessages(): void
    {
        $listener = new InvoiceEmailTemplateListener($this->createResolver(new Company(), 'sleek'));

        $message = new Email()->to('test@example.com')->from('from@example.com')->text('hi');
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        $this->expectNotToPerformAssertions();
    }

    public function testRunsBeforeTheBodyRenderer(): void
    {
        $events = InvoiceEmailTemplateListener::getSubscribedEvents();

        self::assertSame(['__invoke', 100], $events[MessageEvent::class]);
    }

    private function createResolver(Company $company, ?string $slug): BillingTemplateResolver
    {
        $registry = new BillingTemplateRegistry([
            BillingDocumentType::Invoice->value => $this->invoiceDir,
        ]);

        $toggle = M::mock(ToggleInterface::class);
        $toggle->allows('isActive')->with('saas_enabled')->andReturnTrue();

        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->allows('get')
            ->with(BillingTemplateResolver::TEMPLATE_SETTING_KEY, $company)
            ->andReturn($slug);

        $subscriptionGate = M::mock(PaidSubscriptionGateInterface::class);
        $subscriptionGate->allows('isActive')->with($company)->andReturnTrue();

        $featureGate = M::mock(FeatureGate::class);
        $featureGate->allows('isEnabled')
            ->with(Feature::CustomTemplates->value, $company)
            ->andReturnTrue();

        return new BillingTemplateResolver($registry, $systemConfig, $featureGate, $subscriptionGate, $toggle);
    }
}
