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

namespace Augias\AccountingBundle\Action;

use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Regime\RegimeInterface;
use Augias\AccountingBundle\Regime\RegimeRegistry;
use Augias\AccountingBundle\Service\AccountingPeriodManager;
use Augias\AccountingBundle\Service\AccountingProfileProvider;
use Augias\AccountingBundle\Service\CurrentCompany;
use Augias\CoreBundle\Entity\Company;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Brings a period into being that nothing has been booked into.
 *
 * Periods are created when an entry is filed into one, so a quarter in which
 * nothing was received has no row — and a regime that still wants a nil return
 * for it has nothing to attach one to. This is the user saying "that quarter
 * happened, and I have to declare it", which is an accounting act and not
 * something a background job should decide on their behalf.
 *
 * The period is created open, at zero, and nothing else: closing it is still
 * one-way and still deliberate, and a late entry may yet turn up for it. From
 * here it goes through the ordinary flow — close, declare, record the
 * reference — which is what leaves the nil return an actual history rather than
 * an absence.
 *
 * A POST behind a token, like closing, because it writes.
 *
 * @see \Augias\AccountingBundle\Tests\Functional\CreatePeriodTest
 */
final readonly class CreatePeriod
{
    public const string CSRF_TOKEN_ID = 'accounting_period_create';

    public function __construct(
        private AccountingProfileProvider $profileProvider,
        private RegimeRegistry $registry,
        private CurrentCompany $currentCompany,
        private AccountingPeriodManager $periodManager,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request, Session $session): Response
    {
        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, (string) $request->request->get('_token')))) {
            $session->getFlashBag()->add('danger', 'accounting.entry.flash.invalid_token');

            return $this->back();
        }

        $profile = $this->profileProvider->forCompany();
        $company = $this->currentCompany->get();

        // Same gate as everywhere else in the module: a company that has not
        // chosen a regime has no periodicity to create a period for.
        if (! $this->registry->forProfile($profile) instanceof RegimeInterface || ! $company instanceof Company) {
            $session->getFlashBag()->add('warning', 'accounting.period.flash.not_configured');

            return $this->back();
        }

        try {
            // Any date inside the period is enough — the manager derives the
            // year, the ordinal and the boundaries from the periodicity, so this
            // cannot disagree with a period created by filing an entry.
            $date = new DateTimeImmutable((string) $request->request->get('date'));
        } catch (Exception) {
            $session->getFlashBag()->add('danger', 'accounting.period.flash.bad_date');

            return $this->back();
        }

        // The cycle the button belongs to. VAT can run on its own, and a
        // quarter created where a year was asked for would be a period nobody
        // can declare.
        $type = PeriodType::tryFrom((string) $request->request->get('type'))
            ?? $profile->declarationPeriodicity;

        // periodFor() returns the existing row when there is one, so a double
        // submit or a stale button creates nothing and says the same thing.
        $this->periodManager->periodFor($company, $type, $date, $profile->fiscalYearStartMonth);
        $this->entityManager->flush();

        $session->getFlashBag()->add('success', 'accounting.period.flash.created');

        return $this->back();
    }

    private function back(): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('_accounting_declarations'));
    }
}
