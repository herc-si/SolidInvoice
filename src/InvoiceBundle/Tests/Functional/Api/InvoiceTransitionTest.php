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

namespace Augias\InvoiceBundle\Tests\Functional\Api;

use Augias\ApiBundle\Test\ApiTestCase;
use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\ClientBundle\Test\Factory\ContactFactory;
use Augias\CoreBundle\Company\CompanySelector;
use Augias\CoreBundle\Test\Factory\CompanyFactory;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Test\Factory\InvoiceFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

#[Group('functional')]
final class InvoiceTransitionTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function testAcceptInvoice(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Draft,
            'users' => $contacts,
        ]);

        $result = $this->requestPost(
            sprintf('/api/invoices/%s/transitions/accept', $invoice->getId()),
            []
        );

        self::assertSame('pending', $result['status']);
    }

    public function testCancelInvoice(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Draft,
            'users' => $contacts,
        ]);

        $result = $this->requestPost(
            sprintf('/api/invoices/%s/transitions/cancel', $invoice->getId()),
            []
        );

        self::assertSame('cancelled', $result['status']);
    }

    public function testInvalidTransition(): void
    {
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::Draft]);

        self::$client->request(
            'POST',
            sprintf('/api/invoices/%s/transitions/pay', $invoice->getId()),
            [
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
                'json' => [],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTransitionOnForeignCompanyInvoice(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignClient = ClientFactory::createOne(['company' => $otherCompany]);
        $foreignInvoice = InvoiceFactory::createOne([
            'client' => $foreignClient,
            'status' => InvoiceStatus::Draft,
        ]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request(
            'POST',
            sprintf('/api/invoices/%s/transitions/accept', $foreignInvoice->getId()),
            [
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
                'json' => [],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
