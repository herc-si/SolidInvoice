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

namespace Augias\ClientBundle\Tests\Functional;

use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\ClientBundle\Test\Factory\ContactFactory;
use Augias\CoreBundle\Test\Traits\DoctrineTestTrait;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A client's contacts are rendered into invoice and quote forms, so the order they come
 * back in is user-visible and ends up in snapshots. Without an explicit order the database
 * chooses, and PostgreSQL does not choose insertion order.
 */
#[Group('functional')]
final class ContactOrderingTest extends KernelTestCase
{
    use DoctrineTestTrait;

    public function testContactsAreReturnedInAscendingIdOrder(): void
    {
        $client = ClientFactory::createOne(['name' => 'Ordering Test', 'currencyCode' => 'USD']);

        $expected = [];

        foreach (['Aaron', 'Zoe', 'Mia', 'Ben'] as $firstName) {
            $contact = ContactFactory::createOne([
                'firstName' => $firstName,
                'lastName' => 'Example',
                'email' => strtolower($firstName) . '@example.com',
                'client' => $client,
            ]);

            $expected[] = $contact->getId()->toRfc4122();
        }

        // The mapping promises ascending id, not insertion order. Those coincide while ULID
        // generation is monotonic, which is what makes the invoice-form snapshots stable,
        // but asserting the weaker contract keeps this test about the ORDER BY.
        sort($expected);

        $id = $client->getId();
        $this->em->clear();

        $reloaded = $this->em->find(Client::class, $id);

        self::assertInstanceOf(Client::class, $reloaded);

        $actual = array_map(
            static fn ($contact): string => $contact->getId()->toRfc4122(),
            $reloaded->getContacts()->toArray()
        );

        self::assertSame($expected, array_values($actual));
    }
}
