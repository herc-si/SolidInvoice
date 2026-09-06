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

use Augias\MailerBundle\Configurator\GmailConfigurator;
use Augias\MailerBundle\Form\Type\TransportConfig\UsernamePasswordTransportConfigType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\Dsn;

final class GmailConfiguratorTest extends TestCase
{
    public function testName(): void
    {
        self::assertSame('Gmail', new GmailConfigurator()->getName());
    }

    public function testForm(): void
    {
        self::assertSame(UsernamePasswordTransportConfigType::class, new GmailConfigurator()->getForm());
    }

    public function testConfigure(): void
    {
        self::assertEquals(Dsn::fromString('gmail+smtp://foo:bar@default'), new GmailConfigurator()->configure(['username' => 'foo',  'password' => 'bar']));
    }
}
