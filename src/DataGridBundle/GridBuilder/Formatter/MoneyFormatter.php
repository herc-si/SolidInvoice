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

namespace Augias\DataGridBundle\GridBuilder\Formatter;

use Augias\DataGridBundle\GridBuilder\Column\Column;
use Augias\MoneyBundle\Formatter\MoneyFormatterInterface;
use Augias\SettingsBundle\SystemConfig;
use Money\Money;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @see \Augias\DataGridBundle\Tests\GridBuilder\Formatter\MoneyFormatterTest
 */
final readonly class MoneyFormatter implements FormatterInterface
{
    public function __construct(
        private SystemConfig $config,
        private MoneyFormatterInterface $moneyFormatter
    ) {
    }

    public function format(Column $column, mixed $value): string | TranslatableMessage
    {
        if (! $value instanceof Money) {
            $value = new Money((string) $value, $this->config->getCurrency());
        }

        return $this->moneyFormatter->format($value);
    }
}
