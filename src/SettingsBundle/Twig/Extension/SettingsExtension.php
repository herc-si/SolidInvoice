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

namespace Augias\SettingsBundle\Twig\Extension;

use const JSON_THROW_ON_ERROR;
use Augias\ClientBundle\Entity\Address;
use Augias\SettingsBundle\SystemConfig;
use Doctrine\DBAL\Exception;
use JsonException;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function json_decode;

/**
 * @see \Augias\SettingsBundle\Tests\Twig\Extension\SettingsExtensionTest
 */
class SettingsExtension extends AbstractExtension
{
    public function __construct(
        private readonly SystemConfig $config
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('setting', fn (string $setting, $default = null, $decode = false) => $this->getSetting($setting, $default, $decode)),
            new TwigFunction('address', fn (array $address) => $this->renderAddress($address)),
        ];
    }

    /**
     * @param array<string, string|null> $address
     */
    public function renderAddress(array $address): string
    {
        return (string) Address::fromArray($address);
    }

    /**
     * @param mixed|null $default
     *
     * @return mixed|null
     *
     * @throws JsonException
     */
    public function getSetting(string $key, $default = null, bool $decode = false)
    {
        try {
            $setting = $this->config->get($key);
        } catch (Exception) {
            return $default;
        }

        if (null === $setting) {
            return $default;
        }

        if ($decode && $setting) {
            return json_decode($setting, true, 512, JSON_THROW_ON_ERROR);
        }

        return $setting;
    }
}
