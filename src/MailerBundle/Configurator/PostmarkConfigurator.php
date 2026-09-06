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

use Augias\MailerBundle\Form\Type\TransportConfig\KeyTransportConfigType;
use Symfony\Component\Mailer\Transport\Dsn;

/**
 * @see \Augias\MailerBundle\Tests\Configurator\PostmarkConfiguratorTest
 */
final class PostmarkConfigurator implements ConfiguratorInterface
{
    public function getForm(): string
    {
        return KeyTransportConfigType::class;
    }

    public function getName(): string
    {
        return 'Postmark';
    }

    /**
     * @param array<string, mixed> $config
     */
    public function configure(array $config): Dsn
    {
        return Dsn::fromString(\sprintf('postmark+api://%s@default', $config['key']));
    }
}
