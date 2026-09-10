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

namespace Augias\AccountingBundle\Tests\Attention;

use Augias\AccountingBundle\AccountingSettings;
use Augias\AccountingBundle\Attention\ThresholdAlertsSource;
use Augias\AccountingBundle\Entity\ThresholdAlert;
use Augias\AccountingBundle\Tests\Dashboard\AccountingWidgetTestCase;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ThresholdAlertsSource::class)]
final class ThresholdAlertsSourceTest extends AccountingWidgetTestCase
{
    public function testDoesNotApplyUntilARegimeIsChosen(): void
    {
        $this->config->set(AccountingSettings::REGIME, '');

        self::assertFalse($this->source()->supports());
    }

    public function testAppliesOnceARegimeIsChosen(): void
    {
        $this->configureMicroEntreprise();

        self::assertTrue($this->source()->supports());
    }

    /**
     * A company that has crossed nothing contributes no section, so the card's
     * "All caught up" is not printed above an empty header.
     */
    public function testContributesNothingWhenNoLimitHasBeenCrossed(): void
    {
        $this->configureMicroEntreprise();

        self::assertFalse($this->source()->hasItems());
    }

    public function testReportsACrossingThatWasRaisedThisYear(): void
    {
        $this->configureMicroEntreprise();
        $this->alert('micro_ceiling.services_bnc', 80);

        $source = $this->source();

        self::assertTrue($source->hasItems());
        self::assertCount(1, $source->getData()['alerts']);
    }

    /**
     * Annual cumulative turnover resets in January, and so does what the card
     * has to say about it.
     */
    public function testLastYearsCrossingsAreNotReported(): void
    {
        $this->configureMicroEntreprise();
        $this->alert('micro_ceiling.services_bnc', 100, (int) new DateTimeImmutable('today')->format('Y') - 1);

        self::assertFalse($this->source()->hasItems());
    }

    /**
     * A limit passed outright comes before one that is merely being approached.
     */
    public function testTheWorstCrossingComesFirst(): void
    {
        $this->configureMicroEntreprise();
        $this->alert('vat_franchise.base.services_bnc', 80);
        $this->alert('micro_ceiling.services_bnc', 100);

        $alerts = $this->source()->getData()['alerts'];

        self::assertSame(100, $alerts[0]->getStep());
        self::assertSame(80, $alerts[1]->getStep());
    }

    /**
     * The label cannot be derived from the key by string surgery —
     * `vat_franchise.base.x` is named by `accounting.threshold.vat_franchise_base`
     * — so it is looked up on the regime that defines both.
     */
    public function testNamesTheThresholdFromTheRegimeThatDefinesIt(): void
    {
        $this->configureMicroEntreprise();
        $this->alert('vat_franchise.base.services_bnc', 100);

        $labels = $this->source()->getData()['thresholdLabels'];

        self::assertSame(
            'accounting.threshold.vat_franchise_base',
            $labels['vat_franchise.base.services_bnc'] ?? null,
        );
    }

    public function testRendersAsASectionOfTheAttentionCard(): void
    {
        $this->configureMicroEntreprise();
        $this->alert('micro_ceiling.services_bnc', 100, triggeredAt: new DateTimeImmutable('2026-07-04'));

        $twig = self::getContainer()->get('twig');
        $source = $this->source();

        $this->assertMatchesHtmlSnapshot(
            $this->normalise($twig->render($source->getTemplate(), $source->getData())),
        );
    }

    private function alert(
        string $key,
        int $step,
        ?int $year = null,
        ?DateTimeImmutable $triggeredAt = null,
    ): ThresholdAlert {
        $alert = new ThresholdAlert()
            ->setThresholdKey($key)
            ->setYear($year ?? (int) new DateTimeImmutable('today')->format('Y'))
            ->setStep($step)
            ->setAmount(BigInteger::of(6_500_000))
            ->setThresholdAmount(BigInteger::of(7_770_000))
            ->setCurrencyCode('EUR')
            ->setTriggeredAt($triggeredAt ?? new DateTimeImmutable('2026-07-04'));

        $alert->setCompany($this->companyReference());

        $this->entityManager->persist($alert);
        $this->entityManager->flush();

        return $alert;
    }

    private function source(): ThresholdAlertsSource
    {
        $source = self::getContainer()->get(ThresholdAlertsSource::class);
        self::assertInstanceOf(ThresholdAlertsSource::class, $source);

        return $source;
    }
}
