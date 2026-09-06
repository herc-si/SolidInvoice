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

namespace Augias\ClientBundle\DummyData;

use Augias\ClientBundle\Entity\Address;
use Augias\ClientBundle\Entity\Client;
use Augias\ClientBundle\Entity\Contact;
use Augias\ClientBundle\Enum\ClientStatus;
use Augias\CoreBundle\DummyData\DummyDataLoaderInterface;
use Augias\CoreBundle\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use function assert;
use function random_int;
use function substr;

#[AsTaggedItem(priority: 90)]
final readonly class ClientDummyDataLoader implements DummyDataLoaderInterface
{
    private Generator $faker;

    public function __construct(
        private ManagerRegistry $registry
    ) {
        $this->faker = Factory::create();
    }

    public static function getPriority(): int
    {
        return 90;
    }

    public function load(Company $company): void
    {
        $em = $this->registry->getManager();
        assert($em instanceof EntityManagerInterface);

        // $currencies = ['USD', 'EUR', 'GBP', 'AUD', 'CAD'];
        $currencies = ['USD'];

        for ($i = 0; $i < 10; ++$i) {
            $client = new Client();
            $client->setName(substr($this->faker->company(), 0, 125))
                ->setWebsite(substr($this->faker->url(), 0, 125))
                ->setStatus(ClientStatus::Active)
                ->setCurrencyCode($currencies[array_rand($currencies)])
                ->setCompany($company);

            $contactCount = random_int(1, 3);
            for ($j = 0; $j < $contactCount; ++$j) {
                $contact = new Contact();
                $contact->setFirstName($this->faker->firstName())
                    ->setLastName($this->faker->lastName())
                    ->setEmail($this->faker->safeEmail())
                    ->setCompany($company);

                $client->addContact($contact);
            }

            $addressCount = random_int(1, 2);
            for ($k = 0; $k < $addressCount; ++$k) {
                $address = new Address();
                $address->setStreet1($this->faker->streetAddress())
                    ->setCity($this->faker->city())
                    ->setState($this->faker->lexify('??'))
                    ->setZip($this->faker->postcode())
                    ->setCountry($this->faker->countryCode())
                    ->setCompany($company);

                $client->addAddress($address);
            }

            $em->persist($client);
        }

        $em->flush();
    }
}
