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

/*
 * ---------------------------------------------------------------------------
 * UNVERIFIED REGULATORY DATA — MUST BE CHECKED BEFORE RELYING ON IT
 * ---------------------------------------------------------------------------
 *
 * Every figure below is a *starting point*, not an authority. French
 * micro-entreprise rates and thresholds are revised by each finance act, are
 * sometimes changed mid-year, and at least one recent change (the single
 * 25,000 EUR VAT threshold) was voted and then suspended. Nothing here has
 * been checked against an official source.
 *
 * Confirm against:
 *   - urssaf.fr / autoentrepreneur.urssaf.fr  (contribution and training rates)
 *   - impots.gouv.fr / BOFiP                  (regime ceilings, VAT franchise)
 *
 * Each entry carries `verified => false` until someone has done that; the UI
 * shows the rate vintage next to every computed figure, and every declaration
 * line stays editable, precisely so a stale rate here is a correctable
 * annoyance rather than a wrong filing.
 *
 * Shape
 * -----
 * Both sections are lists of dated entries, ordered oldest first. A lookup
 * takes the last entry whose `effective_from` is on or before the date being
 * resolved — so a period is always measured against the rules in force at the
 * time, not today's.
 *
 * Money is in minor units (cents). Rates are percentages, as strings, to keep
 * them out of floating point.
 */

return [
    'thresholds' => [
        [
            'effective_from' => '2023-01-01',
            'verified' => false,
            // Plafonds du régime micro (CA annuel).
            'micro_ceiling' => [
                'sale_of_goods' => 18_870_000,
                'services_bic' => 7_770_000,
                'services_bnc' => 7_770_000,
            ],
            // Franchise en base de TVA — seuil de base.
            'vat_franchise_base' => [
                'sale_of_goods' => 8_500_000,
                'services_bic' => 3_750_000,
                'services_bnc' => 3_750_000,
            ],
            // Seuil majoré (tolérance) : au-delà, la TVA devient due.
            'vat_franchise_tolerance' => [
                'sale_of_goods' => 9_350_000,
                'services_bic' => 4_125_000,
                'services_bnc' => 4_125_000,
            ],
        ],
    ],

    'contributions' => [
        [
            'effective_from' => '2024-01-01',
            'verified' => false,
            // Cotisations sociales. BNC is split because SSI and CIPAV charge
            // differently on identical turnover.
            'social' => [
                'sale_of_goods' => '12.3',
                'services_bic' => '21.2',
                'services_bnc_ssi' => '23.1',
                'services_bnc_cipav' => '23.2',
            ],
            // Contribution à la formation professionnelle. NOTE: the real split
            // is commerçant 0.1 / artisan 0.3 / services and libéral 0.2, and
            // Augias does not record whether a BIC company is a trader or a
            // craftsman — so the BIC services figure here is the one to check
            // first for an artisan.
            'training' => [
                'sale_of_goods' => '0.1',
                'services_bic' => '0.3',
                'services_bnc' => '0.2',
            ],
            // Versement libératoire de l'impôt sur le revenu, when opted into.
            'income_tax' => [
                'sale_of_goods' => '1.0',
                'services_bic' => '1.7',
                'services_bnc' => '2.2',
            ],
            // ACRE: percentage taken off the social rate, and for how long.
            'acre' => [
                'reduction_percent' => '50',
                'duration_months' => 12,
            ],
        ],
        [
            'effective_from' => '2025-01-01',
            'verified' => false,
            'social' => [
                'sale_of_goods' => '12.3',
                'services_bic' => '21.2',
                'services_bnc_ssi' => '24.6',
                'services_bnc_cipav' => '23.2',
            ],
            'training' => [
                'sale_of_goods' => '0.1',
                'services_bic' => '0.3',
                'services_bnc' => '0.2',
            ],
            'income_tax' => [
                'sale_of_goods' => '1.0',
                'services_bic' => '1.7',
                'services_bnc' => '2.2',
            ],
            'acre' => [
                'reduction_percent' => '50',
                'duration_months' => 12,
            ],
        ],
        [
            'effective_from' => '2026-01-01',
            'verified' => false,
            'social' => [
                'sale_of_goods' => '12.3',
                'services_bic' => '21.2',
                'services_bnc_ssi' => '26.1',
                'services_bnc_cipav' => '23.2',
            ],
            'training' => [
                'sale_of_goods' => '0.1',
                'services_bic' => '0.3',
                'services_bnc' => '0.2',
            ],
            'income_tax' => [
                'sale_of_goods' => '1.0',
                'services_bic' => '1.7',
                'services_bnc' => '2.2',
            ],
            'acre' => [
                'reduction_percent' => '50',
                'duration_months' => 12,
            ],
        ],
    ],
];
