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

namespace Augias\AccountingBundle\Regime;

use Augias\AccountingBundle\Exception\UnknownRegimeException;
use Augias\AccountingBundle\Model\AccountingProfile;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Traversable;
use function array_keys;
use function iterator_to_array;

/**
 * Every registered tax regime, addressable by its code.
 *
 * Mirrors {@see \Augias\ElectronicInvoicingBundle\Provider\ElectronicInvoiceProviderRegistry}:
 * implementations are tagged by interface and injected as an iterator, so
 * adding a regime is a matter of writing the class and nothing else.
 */
final class RegimeRegistry
{
    /** @var array<string, RegimeInterface>|null */
    private ?array $regimes = null;

    /**
     * @param iterable<RegimeInterface> $taggedRegimes
     */
    public function __construct(
        #[AutowireIterator(RegimeInterface::class)]
        private readonly iterable $taggedRegimes,
    ) {
    }

    /**
     * @throws UnknownRegimeException when no regime carries that code
     */
    public function get(string $code): RegimeInterface
    {
        $regimes = $this->indexed();

        if (! isset($regimes[$code])) {
            throw UnknownRegimeException::forCode($code, array_keys($regimes));
        }

        return $regimes[$code];
    }

    /**
     * The regime a company is on, or null when it has not chosen one — or has
     * one recorded that no longer exists, which can happen when a deployment
     * drops a regime the settings still point at. Callers show the setup
     * prompt in both cases rather than failing.
     */
    public function forProfile(AccountingProfile $profile): ?RegimeInterface
    {
        if (! $profile->isConfigured()) {
            return null;
        }

        return $this->indexed()[$profile->regimeCode] ?? null;
    }

    public function has(string $code): bool
    {
        return isset($this->indexed()[$code]);
    }

    /**
     * @return array<string, RegimeInterface>
     */
    public function all(): array
    {
        return $this->indexed();
    }

    /**
     * Form choices, grouped by country so a list that eventually spans several
     * jurisdictions stays navigable.
     *
     * @return array<string, array<string, string>> country code => label key => regime code
     */
    public function choices(): array
    {
        $choices = [];

        foreach ($this->indexed() as $code => $regime) {
            $choices[$regime->countryCode()][$regime->labelKey()] = $code;
        }

        return $choices;
    }

    /**
     * @return array<string, RegimeInterface>
     */
    private function indexed(): array
    {
        if (null === $this->regimes) {
            $regimes = [];

            $tagged = $this->taggedRegimes instanceof Traversable
                ? iterator_to_array($this->taggedRegimes)
                : $this->taggedRegimes;

            foreach ($tagged as $regime) {
                $regimes[$regime->code()] = $regime;
            }

            $this->regimes = $regimes;
        }

        return $this->regimes;
    }
}
