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

namespace Augias\MailerBundle\Configurator;

use Augias\MailerBundle\Form\Type\TransportConfig\MailgunApiTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;

/**
 * @see \Augias\MailerBundle\Tests\Configurator\MailgunConfiguratorTest
 */
final class MailgunConfigurator implements ConfiguratorInterface
{
    public function getForm(): string
    {
        return MailgunApiTransportConfigType::class;
    }

    public function getName(): string
    {
        return 'Mailgun';
    }

    /**
     * @param array<string, mixed> $config
     */
    public function configure(array $config): Dsn
    {
        return Dsn::fromString(\sprintf('mailgun+api://%s:%s@default', $config['key'], $config['domain']));
    }
}
