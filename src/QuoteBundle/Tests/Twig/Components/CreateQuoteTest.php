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

namespace Augias\QuoteBundle\Tests\Twig\Components;

use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\ClientBundle\Test\Factory\ContactFactory;
use Augias\CoreBundle\Test\LiveComponentTest;
use Augias\QuoteBundle\DTO\QuoteFormDTO;
use Augias\QuoteBundle\Entity\Line;
use Augias\QuoteBundle\Twig\Components\CreateQuote;
use Augias\TaxBundle\Entity\Tax;
use Brick\Math\Exception\MathException;

final class CreateQuoteTest extends LiveComponentTest
{
    public function testCreateQuote(): void
    {
        $dto = new QuoteFormDTO();

        $component = $this->createLiveComponent(
            name: CreateQuote::class,
            data: ['dto' => $dto]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));
    }

    /**
     * @throws MathException
     */
    public function testCreateQuoteWithMultipleLines(): void
    {
        $dto = new QuoteFormDTO();
        $dto->lines->add(new Line()->setPrice(10000)->setQty(1));
        $dto->lines->add(new Line()->setPrice(10000)->setQty(1));

        $component = $this->createLiveComponent(
            name: CreateQuote::class,
            data: ['dto' => $dto]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));
    }

    /**
     * @throws MathException
     */
    public function testCreateQuoteWithTaxRates(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();

        $tax = new Tax()
            ->setName('VAT')
            ->setRate(20)
            ->setType(Tax::TYPE_INCLUSIVE);

        $em->persist($tax);

        $em->flush();

        $dto = new QuoteFormDTO();
        $dto->lines->add(new Line()->setPrice(10000)->setQty(1));

        $component = $this->createLiveComponent(
            name: CreateQuote::class,
            data: ['dto' => $dto]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));
    }

    /**
     * Tests that contacts are auto-selected when a client is pre-selected.
     * The component's PostMount hook should auto-select all contacts.
     *
     * @throws MathException
     */
    public function testCreateQuoteWithPreselectedClientAutoSelectsContacts(): void
    {
        $client = ClientFactory::createOne([
            'name' => 'Test Client',
            'currencyCode' => 'USD',
        ]);

        ContactFactory::createOne([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'client' => $client,
        ]);

        ContactFactory::createOne([
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
            'client' => $client,
        ]);

        $dto = new QuoteFormDTO();
        $dto->client = $client;
        $dto->lines->add(new Line()->setPrice(10000)->setQty(1));

        $component = $this->createLiveComponent(
            name: CreateQuote::class,
            data: ['dto' => $dto]
        )->actingAs($this->getUser());

        $rendered = $component->render()
            ->toString();

        // Verify both contacts are displayed
        self::assertStringContainsString('John Doe', $rendered);
        self::assertStringContainsString('Jane Smith', $rendered);

        // Verify checkboxes are checked (contacts are selected by PostMount hook)
        self::assertStringContainsString('checked', $rendered);

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));
    }

    /**
     * Tests that the component correctly tracks previous client ID.
     * The PostMount hook should set previousClientId when auto-selecting contacts.
     */
    public function testPreviousClientIdIsTracked(): void
    {
        $client = ClientFactory::createOne([
            'name' => 'Test Client',
            'currencyCode' => 'USD',
        ]);

        ContactFactory::createOne([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'client' => $client,
        ]);

        $dto = new QuoteFormDTO();
        $dto->client = $client;

        $component = $this->createLiveComponent(
            name: CreateQuote::class,
            data: ['dto' => $dto]
        )->actingAs($this->getUser());

        // Render the component
        $component->render();

        // Access the component instance to verify previousClientId is set
        $componentInstance = $component->component();

        self::assertInstanceOf(CreateQuote::class, $componentInstance);
        self::assertSame((string) $client->getId(), $componentInstance->previousClientId);
    }
}
