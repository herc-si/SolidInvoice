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

namespace Augias\CoreBundle\Generator;

use Augias\CoreBundle\Generator\BillingIdGenerator\IdGeneratorInterface;
use Augias\InvoiceBundle\Entity\Invoice;
use Augias\QuoteBundle\Entity\Quote;
use Augias\SettingsBundle\SystemConfig;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @see \Augias\CoreBundle\Tests\Generator\BillingIdGeneratorTest
 */
final readonly class BillingIdGenerator
{
    /**
     * @param ServiceLocator<IdGeneratorInterface> $generators
     */
    public function __construct(
        #[AutowireLocator(IdGeneratorInterface::class)]
        private ServiceLocator $generators,
        private SystemConfig $config,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws ContainerExceptionInterface
     */
    public function generate(object $entity, array $options = [], ?string $strategy = null): string
    {
        $settingSection = match (true) {
            $entity instanceof Invoice => 'invoice',
            $entity instanceof Quote => 'quote',
            default => throw new InvalidArgumentException('Invalid entity type'),
        };

        $strategy = $strategy ?: $this->config->get($settingSection . '/id_generation/strategy');

        $prefix = $this->config->get($settingSection . '/id_generation/id_prefix') ?? '';
        $suffix = $this->config->get($settingSection . '/id_generation/id_suffix') ?? '';

        // Pass prefix and suffix to the generator so it can handle them if needed
        $options['prefix'] = $prefix;
        $options['suffix'] = $suffix;

        $invoiceId = $this->generators->get($strategy ?? 'auto_increment')
            ->generate($entity, $options);

        return sprintf(
            '%s%s%s',
            $prefix,
            $invoiceId,
            $suffix
        );
    }
}
