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

namespace Augias\InvoiceBundle\Tests\Functional;

use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\ClientBundle\Test\Factory\ContactFactory;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\InvoiceBundle\Enum\InvoiceStatus;
use Augias\InvoiceBundle\Repository\InvoiceRepository;
use Augias\InvoiceBundle\Test\Factory\InvoiceFactory;
use Carbon\CarbonImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class QuickInvoiceQueryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testCanFindInvoiceNeedingReminder(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::now()->modify('+3 days')->setTime(0, 0)->modify('+6 hours'),
            'users' => [$contact],
        ]);

        $repository = self::getContainer()->get(InvoiceRepository::class);
        $results = iterator_to_array($repository->getInvoicesNeedingPreDueReminders(3));

        self::assertCount(1, $results, 'Should find the invoice');
    }
}
