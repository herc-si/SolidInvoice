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

namespace SolidInvoice\CatalogBundle\Tests\Twig\Components;

use Brick\Math\BigInteger;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\CatalogBundle\Entity\Product;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\InvoiceBundle\Twig\Components\CreateInvoice;
use SolidInvoice\QuoteBundle\Twig\Components\CreateQuote;
use SolidInvoice\TaxBundle\Entity\Tax;

/**
 * The catalogue only pays for itself if picking an entry fills the line in
 * completely; a picker that inserted a blank row would be slower than typing.
 */
final class CatalogPickerTest extends LiveComponentTest
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function componentProvider(): iterable
    {
        yield 'invoice' => [CreateInvoice::class];
        yield 'quote' => [CreateQuote::class];
    }

    /**
     * @param class-string $componentClass
     */
    #[DataProvider('componentProvider')]
    public function testPickingAnEntryAddsAFullyFilledLine(string $componentClass): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $tax = new Tax();
        $tax->setCompany($this->company)->setName('TVA 20')->setRate(20.0)->setType('Inclusive');
        $entityManager->persist($tax);

        $product = new Product();
        $product->setCompany($this->company)
            ->setName('Journée de conseil')
            ->setDescription('Accompagnement sur site')
            ->setSalePrice(BigInteger::of(90000))
            ->setTax($tax);
        $entityManager->persist($product);
        $entityManager->flush();

        $component = $this->createLiveComponent(name: $componentClass, data: [], client: $this->client)
            ->actingAs($this->getUser());

        $component->set('catalogProductId', (string) $product->getId())->call('addFromCatalog');

        $lines = $component->component()->formValues['lines'] ?? [];

        self::assertCount(1, $lines);

        $line = reset($lines);

        // Name and description are separate columns in the catalogue but a
        // single text field on the line, so they arrive joined.
        self::assertSame("Journée de conseil\nAccompagnement sur site", $line['description']);
        self::assertSame('1', $line['qty']);
        self::assertStringStartsWith('90000', $line['price']);
        self::assertCount(1, $line['taxes']);
    }

    public function testAnUnknownEntryAddsNothing(): void
    {
        $component = $this->createLiveComponent(name: CreateInvoice::class, data: [], client: $this->client)
            ->actingAs($this->getUser());

        $component->set('catalogProductId', '')->call('addFromCatalog');

        self::assertSame([], $component->component()->formValues['lines'] ?? []);
    }
}
