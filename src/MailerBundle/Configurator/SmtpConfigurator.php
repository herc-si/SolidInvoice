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

use Augias\MailerBundle\Form\Type\TransportConfig\SmtpTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;
use function urlencode;

/**
 * @see \Augias\MailerBundle\Tests\Configurator\SmtpConfiguratorTest
 */
final class SmtpConfigurator implements ConfiguratorInterface
{
    private const int DEFAULT_PORT = 25;

    public function getForm(): string
    {
        return SmtpTransportConfigType::class;
    }

    public function getName(): string
    {
        return 'SMTP';
    }

    /**
     * @param array{user?: string|null, password?: string|null, host: string, port: int|null} $config
     */
    public function configure(array $config): Dsn
    {
        if (empty($config['user']) && empty($config['password'])) {
            return Dsn::fromString(\sprintf('smtp://%s:%d', $config['host'], $config['port'] ?? self::DEFAULT_PORT));
        }

        return Dsn::fromString(\sprintf('smtp://%s:%s@%s:%d', $config['user'], urlencode($config['password'] ?? ''), $config['host'], $config['port'] ?? self::DEFAULT_PORT));
    }
}
