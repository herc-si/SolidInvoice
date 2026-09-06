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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use SolidInvoice\CoreBundle\Test\LiveComponentTest;

/**
 * Recurring invoices have no sidebar entry — these tabs are the only way
 * between the two lists, so losing either one strands a whole section.
 */
final class RecurringInvoiceTabTest extends LiveComponentTest
{
    public function testTheInvoiceListLinksToTheRecurringList(): void
    {
        self::assertContains('/invoices/recurring', $this->headerLinksOf('/invoices/'));
    }

    public function testTheRecurringListLinksBackToTheInvoiceList(): void
    {
        self::assertContains('/invoices/', $this->headerLinksOf('/invoices/recurring'));
    }

    /**
     * @return list<string>
     */
    private function headerLinksOf(string $url): array
    {
        $this->client->loginUser($this->getUser());

        $crawler = $this->client->request('GET', $url);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        return $crawler->filter('.card-header a')->each(
            static fn ($node): string => (string) $node->attr('href')
        );
    }
}
