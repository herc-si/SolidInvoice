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

namespace Augias\InvoiceBundle\Tests\Form\Type;

use Augias\ClientBundle\Entity\Client;
use Augias\CoreBundle\Entity\Discount;
use Augias\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use Augias\CoreBundle\Form\Type\DiscountType;
use Augias\CoreBundle\Repository\CustomFieldRepository;
use Augias\CoreBundle\Repository\CustomFieldValueRepository;
use Augias\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Augias\CoreBundle\Tests\FormTestCase;
use Augias\InvoiceBundle\Entity\RecurringInvoice;
use Augias\InvoiceBundle\Entity\RecurringOptions;
use Augias\InvoiceBundle\Form\Type\ItemType;
use Augias\InvoiceBundle\Form\Type\RecurringInvoiceType;
use Augias\SettingsBundle\SystemConfig;
use Augias\TaxBundle\Service\TaxAvailability;
use Brick\Math\BigDecimal;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Mockery as M;
use Money\Currency;
use Override;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;

final class RecurringInvoiceTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $client = new Client()->setCompany($this->company)->setCurrencyCode('USD');

        $this->registry->getManager()->persist($client);

        $notes = $this->faker->text();
        $terms = $this->faker->text();
        $discountValue = $this->faker->numberBetween(0, 100);
        $formData = [
            'client' => [
                'autocomplete' => (
                    $this->em->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform ?
                    $client->getId()->toString() :
                    $client->getId()->toString()
                ),
            ],
            'discount' => [
                'value' => $discountValue,
                'type' => Discount::TYPE_PERCENTAGE,
            ],
            'lines' => [],
            'notes' => $notes,
            'terms' => $terms,
            'total' => 0,
            'baseTotal' => 0,
            'tax' => 0,
            'date_start' => $this->faker->dateTime(),
        ];

        $object = new RecurringInvoice();
        $object->setRecurringOptions(new RecurringOptions());
        $object->setClient($client);

        $data = clone $object;

        $object->setTerms($terms);
        $object->setNotes($notes);

        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(BigDecimal::of($discountValue)->multipliedBy(100));

        $object->setDiscount($discount);

        $this->assertFormData($this->factory->create(RecurringInvoiceType::class, $data), $formData, $object);
    }

    /**
     * @return array<FormExtensionInterface>
     */
    #[Override]
    protected function getExtensions(): array
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig
            ->shouldReceive('getCurrency')
            ->zeroOrMoreTimes()
            ->andReturn(new Currency('USD'));

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);

        $invoiceType = new RecurringInvoiceType($systemConfig, $this->registry, $featureGate, $this->taxAvailability());
        $itemType = new ItemType($this->taxAvailability());
        $customFieldsType = new CustomFieldValueCollectionType(
            M::mock(CustomFieldRepository::class, ['findByTargetOrdered' => []]),
            M::mock(CustomFieldValueRepository::class, ['findForRecord' => []]),
            new CustomFieldTypeResolver(),
            $this->createStub(EntityManagerInterface::class),
        );

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([
                $invoiceType,
                $itemType,
                new DiscountType($systemConfig),
                $customFieldsType,
            ], []),
        ];
    }

    /**
     * Whether the tax field is offered is a service now, joining "any rates
     * configured" with "the company is liable for VAT". These tests are about
     * the liable case, so it comes from the container rather than a stub.
     */
    private function taxAvailability(): TaxAvailability
    {
        $availability = self::getContainer()->get(TaxAvailability::class);
        self::assertInstanceOf(TaxAvailability::class, $availability);

        return $availability;
    }
}
