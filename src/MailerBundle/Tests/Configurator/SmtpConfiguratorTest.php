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

use Augias\MailerBundle\Configurator\SmtpConfigurator;
use Augias\MailerBundle\Form\Type\TransportConfig\SmtpTransportConfigType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\Dsn;

final class SmtpConfiguratorTest extends TestCase
{
    public function testName(): void
    {
        self::assertSame('SMTP', new SmtpConfigurator()->getName());
    }

    public function testForm(): void
    {
        self::assertSame(SmtpTransportConfigType::class, new SmtpConfigurator()->getForm());
    }

    public function testConfigure(): void
    {
        self::assertEquals(Dsn::fromString('smtp://foo:bar@example.com:465'), new SmtpConfigurator()->configure(['user' => 'foo', 'password' => 'bar', 'host' => 'example.com', 'port' => 465]));
    }
}
