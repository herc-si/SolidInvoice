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

namespace Augias\AccountingBundle\Service;

use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Model\DeclarationLine;
use Augias\AccountingBundle\Model\DeclarationResult;
use Augias\AccountingBundle\Repository\LedgerEntryRepository;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use function array_values;
use function strcmp;
use function usort;

/**
 * The VAT return, worked out from the books.
 *
 * Nothing here is a rate table or a published figure: VAT is not computed from
 * turnover, it is *collected* on sales and *paid* to suppliers, and both were
 * recorded as they happened. This only gathers them — which is why it has no
 * rate version and cannot go stale.
 *
 * Collected comes out per rate, because a return declares a base and a tax for
 * each. Deducted comes out as a single line, because that is the one box it
 * goes in. The deducted line is negative, so the total of the lines is the
 * balance: positive is owed to the tax office, negative is a credit carried
 * forward.
 *
 * Not a {@see \Augias\AccountingBundle\Regime\RegimeInterface}, deliberately. A
 * regime is what a business is on, and a company is on exactly one; VAT is an
 * obligation that sits alongside whichever one that is. A micro-entrepreneur
 * past the franchise threshold owes both.
 *
 * @see \Augias\AccountingBundle\Tests\Service\VatReturnCalculatorTest
 */
final readonly class VatReturnCalculator
{
    public function __construct(
        private LedgerEntryRepository $entryRepository,
    ) {
    }

    public function calculate(AccountingPeriod $period, string $currencyCode): DeclarationResult
    {
        $tax = $this->entryRepository->taxForPeriod($period);
        $lines = [];
        $turnover = BigInteger::zero();

        $collected = array_values($tax['collected']);

        // By rate, numerically — "5.5" before "20", which sorting the keys as
        // strings would get backwards — so the return reads in the order its
        // boxes do rather than in the order entries happened to be written.
        usort(
            $collected,
            static fn (array $a, array $b): int => BigDecimal::of($a['rate'])->compareTo(BigDecimal::of($b['rate']))
                ?: strcmp($a['category'], $b['category']),
        );

        foreach ($collected as $share) {
            $turnover = $turnover->plus($share['base']);

            $lines[] = new DeclarationLine(
                'vat.collected.' . $share['rate'] . '.' . $share['category'],
                'accounting.declaration.line.vat_collected',
                DeclarationLine::KIND_VAT_COLLECTED,
                $share['base'],
                BigDecimal::of($share['rate']),
                $share['tax'],
                $currencyCode,
                rateOverridable: false,
            );
        }

        if ($tax['deducted']->isPositive()) {
            // Negative, so that the lines add up to what is actually owed.
            // Nothing else in a declaration subtracts, which is why this says so
            // rather than leaving a reader to work out the sign from the kind.
            $lines[] = new DeclarationLine(
                'vat.deductible',
                'accounting.declaration.line.vat_deductible',
                DeclarationLine::KIND_VAT_DEDUCTIBLE,
                BigInteger::zero(),
                BigDecimal::zero(),
                $tax['deducted']->negated(),
                $currencyCode,
                rateOverridable: false,
            );
        }

        return new DeclarationResult(
            $turnover,
            $lines,
            $currencyCode,
        );
    }
}
