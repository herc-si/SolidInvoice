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

namespace Augias\ElectronicInvoicingBundle\Tests\Twig\Components;

use Augias\CoreBundle\Test\LiveComponentTest;
use Augias\ElectronicInvoicingBundle\Entity\ElectronicInvoiceProviderSetting;
use Augias\ElectronicInvoicingBundle\Twig\Components\ElectronicInvoiceMarketplace;

final class ElectronicInvoiceMarketplaceTest extends LiveComponentTest
{
    public function testNoModalByDefault(): void
    {
        $component = $this->createLiveComponent(
            name: ElectronicInvoiceMarketplace::class,
            data: [],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        self::assertStringNotContainsString('modal-backdrop', $rendered);
    }

    /**
     * Regression test: the "Configure" link on an unconfigured provider card
     * navigates with a `selectedProvider` query parameter (matching the
     * LiveProp's own name, since it's bound with `url: true`) — using any
     * other parameter name leaves selectedProvider empty and the modal never
     * opens, which is exactly what happened before this test was added.
     */
    public function testSelectingAnUnconfiguredProviderOpensTheConfigurationModal(): void
    {
        $component = $this->createLiveComponent(
            name: ElectronicInvoiceMarketplace::class,
            data: [
                'selectedProvider' => 'test_provider',
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        self::assertStringContainsString('modal-backdrop', $rendered);
        self::assertStringContainsString('Add Provider', $rendered);
        self::assertStringContainsString('value="test_provider"', $rendered);
    }

    public function testSelectingAnExistingSettingOpensItForEditing(): void
    {
        $user = $this->getUser();

        $setting = new ElectronicInvoiceProviderSetting();
        $setting->setCompany($user->getCompanies()->first())
            ->setName('My Test Provider')
            ->setProvider('test_provider')
            ->setSettings(['reference_prefix' => 'ACME']);

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($setting);
        $entityManager->flush();

        $component = $this->createLiveComponent(
            name: ElectronicInvoiceMarketplace::class,
            data: [
                'selectedSetting' => (string) $setting->getId(),
            ],
            client: $this->client,
        )->actingAs($user);

        $rendered = $component->render()->toString();

        self::assertStringContainsString('modal-backdrop', $rendered);
        self::assertStringContainsString('My Test Provider', $rendered);
        self::assertStringNotContainsString('Add Provider', $rendered);
    }
}
