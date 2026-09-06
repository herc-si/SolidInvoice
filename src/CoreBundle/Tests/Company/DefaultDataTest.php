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

namespace Augias\CoreBundle\Tests\Company;

use Augias\CoreBundle\Company\DefaultData;
use Augias\CoreBundle\Config\SystemConfigProvider;
use Augias\CoreBundle\Entity\Company;
use Augias\CoreBundle\Entity\CustomField\CustomField;
use Augias\InvoiceBundle\Config\ConfigProvider as InvoiceConfigProvider;
use Augias\MailerBundle\Config\ConfigProvider as MailerConfigProvider;
use Augias\PaymentBundle\Entity\PaymentMethod;
use Augias\QuoteBundle\Config\ConfigProvider as QuoteConfigProvider;
use Augias\SettingsBundle\Entity\Setting;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery as M;
use PHPUnit\Framework\TestCase;

final class DefaultDataTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testDefaultData(): void
    {
        $registry = M::mock(ManagerRegistry::class);
        $entityManager = M::mock(EntityManagerInterface::class);

        $registry
            ->expects('getManager')
            ->andReturn($entityManager);

        $entityManager
            ->expects('persist')
            ->with(M::type(Setting::class))
            ->times(26);

        $entityManager->expects('persist')
            ->with(M::type(CustomField::class))
            ->times(3);

        $entityManager->expects('persist')
            ->with(M::type(PaymentMethod::class))
            ->times(3);

        $entityManager
            ->expects('flush')
            ->once();

        $defaultData = new DefaultData($registry, [
            new SystemConfigProvider(),
            new InvoiceConfigProvider(),
            new QuoteConfigProvider(),
            new MailerConfigProvider(),
        ]);

        $company = new Company();
        $company->setName('Test Company');

        $defaultData->__invoke($company, ['currency' => 'EUR']);
    }
}
