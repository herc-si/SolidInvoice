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

namespace Augias\ElectronicInvoicingBundle\Tests\Provider;

use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceSubmission;
use Augias\ElectronicInvoicingBundle\Enum\ElectronicInvoiceProcessingStatus;
use Augias\ElectronicInvoicingBundle\Form\Type\Provider\TestProviderConfigType;
use Augias\ElectronicInvoicingBundle\Provider\TestProvider;
use Augias\InvoiceBundle\Entity\Invoice;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestProvider::class)]
final class TestProviderTest extends TestCase
{
    private TestProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new TestProvider();
    }

    public function testGetName(): void
    {
        self::assertSame('test_provider', TestProvider::getName());
    }

    public function testGetForm(): void
    {
        self::assertSame(TestProviderConfigType::class, $this->provider->getForm());
    }

    public function testSendReturnsSuccessWithDefaultPrefix(): void
    {
        $result = $this->provider->send(new Invoice(), []);

        self::assertTrue($result->success);
        self::assertNotNull($result->externalReference);
        self::assertStringStartsWith('TEST-', $result->externalReference);
        self::assertNull($result->message);
    }

    public function testSendReturnsSuccessWithCustomPrefix(): void
    {
        $result = $this->provider->send(new Invoice(), ['reference_prefix' => 'ACME']);

        self::assertTrue($result->success);
        self::assertStringStartsWith('ACME-', $result->externalReference);
    }

    public function testSendReturnsFailureWhenSimulateFailureIsSet(): void
    {
        $result = $this->provider->send(new Invoice(), ['simulate_failure' => true]);

        self::assertFalse($result->success);
        self::assertNull($result->externalReference);
        self::assertSame('einvoicing.provider.test.simulated_failure', $result->message);
    }

    public function testResolveProcessingStatusReturnsPendingForASuccessfulSubmission(): void
    {
        $submission = new ElectronicInvoiceSubmission()->setSuccess(true);

        self::assertSame(ElectronicInvoiceProcessingStatus::Pending, $this->provider->resolveProcessingStatus($submission));
    }

    public function testResolveProcessingStatusReturnsRejectedForAFailedSubmission(): void
    {
        $submission = new ElectronicInvoiceSubmission()->setSuccess(false);

        self::assertSame(ElectronicInvoiceProcessingStatus::Rejected, $this->provider->resolveProcessingStatus($submission));
    }
}
