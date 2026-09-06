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

use Augias\MailerBundle\Configurator\MailchimpConfigurator;
use Augias\MailerBundle\Form\Type\TransportConfig\KeyTransportConfigType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\Dsn;

final class MailchimpConfiguratorTest extends TestCase
{
    public function testName(): void
    {
        self::assertSame('Mailchimp Mandrill', new MailchimpConfigurator()->getName());
    }

    public function testForm(): void
    {
        self::assertSame(KeyTransportConfigType::class, new MailchimpConfigurator()->getForm());
    }

    public function testConfigure(): void
    {
        self::assertEquals(Dsn::fromString('mandrill+api://foobar@default'), new MailchimpConfigurator()->configure(['key' => 'foobar']));
    }
}
