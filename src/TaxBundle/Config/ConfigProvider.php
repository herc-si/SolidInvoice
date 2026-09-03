<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\TaxBundle\Config;

use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

final class ConfigProvider implements ProviderInterface
{
    /**
     * @return Config[]
     */
    public function provide(array $data): array
    {
        return [
            new Config(
                SystemConfig::ELECTRONIC_INVOICING_CONFIG_PATH,
                '0',
                'tax.electronic_invoicing.description',
                CheckboxType::class,
                ['label' => 'tax.electronic_invoicing.label'],
            ),
        ];
    }
}
