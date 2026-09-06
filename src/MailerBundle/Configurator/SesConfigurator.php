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

use Augias\MailerBundle\Form\Type\TransportConfig\SesTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;

/**
 * @see \Augias\MailerBundle\Tests\Configurator\SesConfiguratorTest
 */
final class SesConfigurator implements ConfiguratorInterface
{
    public function getForm(): string
    {
        return SesTransportConfigType::class;
    }

    public function getName(): string
    {
        return 'Amazon SES';
    }

    /**
     * @param array<string, mixed> $config
     */
    public function configure(array $config): Dsn
    {
        $dsn = \sprintf('ses+api://%s:%s@default', $config['accessKey'], $config['accessSecret']);
        if (\array_key_exists('region', $config) && null !== $config['region']) {
            $dsn .= '?region=' . $config['region'];
        }

        return Dsn::fromString($dsn);
    }
}
