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

namespace Augias\MailerBundle\Tests\Configurator;

use Augias\MailerBundle\Configurator\SesConfigurator;
use Augias\MailerBundle\Form\Type\TransportConfig\SesTransportConfigType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\Dsn;

final class SesConfiguratorTest extends TestCase
{
    public function testName(): void
    {
        self::assertSame('Amazon SES', new SesConfigurator()->getName());
    }

    public function testForm(): void
    {
        self::assertSame(SesTransportConfigType::class, new SesConfigurator()->getForm());
    }

    public function testConfigureWithoutRegion(): void
    {
        self::assertEquals(Dsn::fromString('ses+api://foo:bar@default'), new SesConfigurator()->configure(['accessKey' => 'foo', 'accessSecret' => 'bar']));
    }

    public function testConfigureWithRegion(): void
    {
        self::assertEquals(Dsn::fromString('ses+api://foo:bar@default?region=eu-west-1'), new SesConfigurator()->configure(['accessKey' => 'foo', 'accessSecret' => 'bar', 'region' => 'eu-west-1']));
    }
}
