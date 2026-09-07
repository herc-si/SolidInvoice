<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) HERC SI <opensource@herc-si.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\AccountingBundle\DependencyInjection;

use Augias\AccountingBundle\Regime\RegimeInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use function dirname;

class AugiasAccountingExtension extends Extension
{
    final public const string RATES_FILE_PARAMETER = 'augias_accounting.fr_micro_rates_file';

    public function load(array $configs, ContainerBuilder $container): void
    {
        // Tagged by interface here rather than in services.php so a regime
        // shipped by another bundle — or a plugin — is picked up too. Same
        // mechanism as AugiasSettingsExtension does for its config providers.
        $container->registerForAutoconfiguration(RegimeInterface::class)
            ->addTag(RegimeInterface::class);

        // Where the French rate table is read from. Set as a parameter, and
        // only when nothing has defined it already, so a self-hosted install
        // can point at its own file from config/services.php — the escape hatch
        // for a rate change that lands before the next release does.
        if (! $container->hasParameter(self::RATES_FILE_PARAMETER)) {
            $container->setParameter(
                self::RATES_FILE_PARAMETER,
                dirname(__DIR__) . '/Resources/config/fr_micro_rates.php',
            );
        }

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->import('services/*.php');
    }
}
