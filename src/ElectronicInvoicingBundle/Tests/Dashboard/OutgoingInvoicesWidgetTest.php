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

namespace Augias\ElectronicInvoicingBundle\Tests\Dashboard;

use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\ElectronicInvoicingBundle\Dashboard\OutgoingInvoicesWidget;
use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceSubmission;
use Augias\ElectronicInvoicingBundle\Enum\ElectronicInvoiceProcessingStatus;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Test\Factory\InvoiceFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(OutgoingInvoicesWidget::class)]
final class OutgoingInvoicesWidgetTest extends EInvoicingWidgetTestCase
{
    public function testDoesNotApplyWithoutAnActiveProvider(): void
    {
        self::assertFalse($this->widget()->supports());
    }

    public function testAppliesOnceAProviderIsActive(): void
    {
        $this->activateTestProvider();

        self::assertTrue($this->widget()->supports());
    }

    public function testNothingSentYetIsNotAnError(): void
    {
        $this->activateTestProvider();

        $data = $this->widget()->getData();

        self::assertSame([], $data['submissions']);
        self::assertFalse($data['hasSubmissions']);
        self::assertSame(0, $data['failedTotal']);
    }

    /**
     * The status is Augias's own normalisation of the provider's vocabulary,
     * resolved through the same extension the rest of the UI uses — so a
     * submission cannot read one thing here and another on its own page.
     */
    public function testNormalisesTheProvidersOwnVocabulary(): void
    {
        $this->activateTestProvider();
        $submission = $this->submission(success: true);

        $data = $this->widget()->getData();

        self::assertSame(
            ElectronicInvoiceProcessingStatus::Pending,
            $data['statuses'][(string) $submission->getId()],
        );
    }

    /**
     * A rejection is the only outcome that needs a person, and unlike a pending
     * one it does not resolve itself, so it is counted rather than left to be
     * spotted in a list.
     */
    public function testCountsTheRejectionsSeparately(): void
    {
        $this->activateTestProvider();
        $this->submission(success: true);
        $this->submission(success: false, message: 'Recipient identifier unknown');

        $data = $this->widget()->getData();

        self::assertSame(1, $data['failedTotal']);
        self::assertCount(2, $data['submissions']);
    }

    public function testGetTemplate(): void
    {
        self::assertSame(
            '@AugiasElectronicInvoicing/Widget/outgoing.html.twig',
            $this->widget()->getTemplate(),
        );
    }

    public function testRendersNothingSentYet(): void
    {
        $this->activateTestProvider();

        $this->assertMatchesHtmlSnapshot($this->render($this->widget()));
    }

    public function testRendersARejection(): void
    {
        $this->activateTestProvider();
        $this->submission(success: false, message: 'Recipient identifier unknown');

        $this->assertMatchesHtmlSnapshot($this->render($this->widget()));
    }

    private function submission(bool $success, ?string $message = null): ElectronicInvoiceSubmission
    {
        $submission = new ElectronicInvoiceSubmission();
        $submission->setCompany($this->company)
            ->setInvoice($this->invoice())
            ->setProvider('test_provider')
            ->setSuccess($success)
            ->setMessage($message);

        $this->entityManager->persist($submission);
        $this->entityManager->flush();

        return $submission;
    }

    private function invoice(): Invoice
    {
        return InvoiceFactory::createOne([
            'client' => ClientFactory::createOne([
                'company' => $this->company,
                'currencyCode' => 'EUR',
            ]),
        ]);
    }

    private function widget(): OutgoingInvoicesWidget
    {
        $widget = self::getContainer()->get(OutgoingInvoicesWidget::class);
        self::assertInstanceOf(OutgoingInvoicesWidget::class, $widget);

        return $widget;
    }
}
