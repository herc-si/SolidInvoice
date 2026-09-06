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
use Augias\SettingsBundle\SystemConfig;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Translation\TranslatableMessage;
use function is_string;

/**
 * @see \Augias\DataGridBundle\Tests\GridBuilder\Formatter\CurrencyFormatterTest
 */
final class CurrencyFormatter implements FormatterInterface
{
    /**
     * @var string[]
     */
    private array $currencyList;

    public function __construct(
        private readonly SystemConfig $config,
        string $locale
    ) {
        $this->currencyList = Currencies::getNames($locale);
    }

    public function format(Column $column, mixed $value): string | TranslatableMessage
    {
        $systemDefault = new TranslatableMessage('System Default (%currency%)', ['%currency%' => $this->config->getCurrency()->getCode()]);

        if (! is_string($value)) {
            return $systemDefault;
        }

        return $this->currencyList[$value] ?? $systemDefault;
    }
}
