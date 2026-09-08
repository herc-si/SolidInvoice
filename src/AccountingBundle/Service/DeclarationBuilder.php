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
use Augias\AccountingBundle\Entity\Declaration;
use Augias\AccountingBundle\Enum\DeclarationStatus;
use Augias\AccountingBundle\Exception\UnknownRegimeException;
use Augias\AccountingBundle\Model\AccountingProfile;
use Augias\AccountingBundle\Model\DeclarationResult;
use Augias\AccountingBundle\Regime\RegimeInterface;
use Augias\AccountingBundle\Regime\RegimeRegistry;
use Augias\AccountingBundle\Repository\DeclarationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Turns a period's turnover into the declaration the user has to file.
 *
 * Augias files nothing. It works out the figures, box by box, and the user
 * copies them onto the collecting body's own site — which is why every line
 * carries the base and the rate it came from rather than a single total, and
 * why what the user sees is stored rather than recomputed on every visit.
 *
 * Recomputation stops for good once the user records that they filed: a
 * submitted declaration is the record of what was actually sent, and rates
 * move. A draft, by contrast, is refreshed each time it is looked at, since
 * its period may still be taking entries.
 *
 * @see \Augias\AccountingBundle\Tests\Service\DeclarationBuilderTest
 */
final readonly class DeclarationBuilder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountingProfileProvider $profileProvider,
        private RegimeRegistry $registry,
        private TurnoverCalculator $turnoverCalculator,
        private DeclarationRepository $declarationRepository,
    ) {
    }

    /**
     * The figures for a period, computed fresh and stored nowhere.
     *
     * @throws UnknownRegimeException when the company is on no regime, or on
     *                                one this deployment no longer has
     */
    public function compute(AccountingPeriod $period): DeclarationResult
    {
        $profile = $this->profileProvider->forCompany($period->getCompany());
        $regime = $this->registry->forProfile($profile);

        if (! $regime instanceof RegimeInterface) {
            throw UnknownRegimeException::forCode((string) $profile->regimeCode, []);
        }

        return $regime->calculate(
            $this->turnoverCalculator->forPeriod($period, $profile->currencyCode),
            $profile,
            $period,
        );
    }

    /**
     * The stored declaration for a period, created or refreshed as needed.
     *
     * A period that is closed produces a declaration that is ready to file; one
     * still open produces a draft, so the user can see what the quarter is
     * shaping up to cost before it ends.
     */
    public function forPeriod(AccountingPeriod $period): Declaration
    {
        $existing = $this->declarationRepository->findForPeriod($period);

        if ($existing instanceof Declaration && ! $existing->isRecomputable()) {
            return $existing;
        }

        $profile = $this->profileProvider->forCompany($period->getCompany());
        $result = $this->compute($period);

        $declaration = $existing ?? $this->create($period, $profile);

        $declaration->setRegimeCode((string) $profile->regimeCode)
            ->setRateVersion($result->rateVersion)
            ->setStatus($period->isClosed() ? DeclarationStatus::Ready : DeclarationStatus::Draft)
            ->setCurrencyCode($result->currencyCode)
            ->setTotalTurnover($result->turnover)
            ->setTotalContributions($result->totalContributions())
            ->setTotalDue($result->totalDue())
            ->setLines($result->linesToArray());

        $this->entityManager->flush();

        return $declaration;
    }

    /**
     * Records that the user filed it, with whatever reference they were given.
     *
     * This is the point of no return for the figures: from here the declaration
     * is a record of what was sent, and nothing recomputes it.
     */
    public function markSubmitted(Declaration $declaration, ?string $reference, ?string $notes): void
    {
        $declaration->setStatus(DeclarationStatus::Submitted)
            ->setSubmittedAt(new DateTimeImmutable())
            ->setReference($reference)
            ->setNotes($notes);

        $this->entityManager->flush();
    }

    private function create(AccountingPeriod $period, AccountingProfile $profile): Declaration
    {
        $declaration = new Declaration()
            ->setPeriod($period)
            ->setRegimeCode((string) $profile->regimeCode)
            ->setCurrencyCode($profile->currencyCode);

        $declaration->setCompany($period->getCompany());

        $this->entityManager->persist($declaration);

        return $declaration;
    }
}
