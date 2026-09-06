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

namespace Augias\CoreBundle\Tests\Listener;

use Augias\ClientBundle\Test\Factory\ClientFactory;
use Augias\CoreBundle\Entity\CustomField\CustomField;
use Augias\CoreBundle\Entity\CustomField\CustomFieldValue;
use Augias\CoreBundle\Enum\CustomFieldTarget;
use Augias\CoreBundle\Enum\CustomFieldType;
use Augias\CoreBundle\Repository\CustomFieldValueRepository;
use Augias\CoreBundle\Test\Factory\CompanyFactory;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

#[Group('functional')]
final class CustomFieldValueCleanupListenerTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testValuesDeletedWhenClientIsRemoved(): void
    {
        $company = CompanyFactory::createOne();
        $client = ClientFactory::createOne(['company' => $company]);

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $field = new CustomField()
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setLabel('Department')
            ->setFieldKey('department')
            ->setType(CustomFieldType::TEXT)
            ->setCompany($company);
        $em->persist($field);
        $value = new CustomFieldValue()
            ->setField($field)
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setTargetId($client->getId())
            ->setValue('Sales')
            ->setCompany($company);
        $em->persist($value);
        $em->flush();

        $clientId = $client->getId();

        $em->remove($client);
        $em->flush();
        $em->clear();

        /** @var CustomFieldValueRepository $repo */
        $repo = self::getContainer()->get(CustomFieldValueRepository::class);
        self::assertInstanceOf(Ulid::class, $clientId);
        self::assertSame([], $repo->findForRecord(CustomFieldTarget::CLIENT, $clientId));
    }
}
