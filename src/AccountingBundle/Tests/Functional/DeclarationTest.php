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

namespace Augias\AccountingBundle\Tests\Functional;

use Augias\AccountingBundle\AccountingSettings;
use Augias\AccountingBundle\Action\ClosePeriod;
use Augias\AccountingBundle\Action\CreatePeriod;
use Augias\AccountingBundle\Action\Declaration\Index;
use Augias\AccountingBundle\Action\Declaration\Submit;
use Augias\AccountingBundle\Action\Declaration\View;
use Augias\AccountingBundle\Entity\AccountingPeriod;
use Augias\AccountingBundle\Entity\Declaration;
use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\DeclarationKind;
use Augias\AccountingBundle\Enum\DeclarationStatus;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Enum\PeriodStatus;
use Augias\AccountingBundle\Enum\PeriodType;
use Augias\AccountingBundle\Repository\AccountingPeriodRepository;
use Augias\AccountingBundle\Repository\DeclarationRepository;
use Augias\AccountingBundle\Repository\LedgerEntryRepository;
use Augias\AccountingBundle\Service\AccountingPeriodManager;
use Augias\AccountingBundle\Service\DeclarationBuilder;
use Augias\CoreBundle\Entity\Company;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Augias\SettingsBundle\SystemConfig;
use Augias\UserBundle\Entity\User;
use Augias\UserBundle\Test\Factory\UserFactory;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The declaration a user has to file, from the books it is derived from to the
 * moment they record having filed it.
 */
#[CoversClass(DeclarationBuilder::class)]
#[CoversClass(Index::class)]
#[CoversClass(View::class)]
#[CoversClass(Submit::class)]
#[CoversClass(CreatePeriod::class)]
#[CoversClass(ClosePeriod::class)]
#[Group('functional')]
final class DeclarationTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    /** 10 000 EUR of BNC services, in cents. */
    private const int TURNOVER = 1_000_000;

    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        self::ensureKernelShutdown();

        $this->client = self::createClient();
        $this->client->disableReboot();

        $user = UserFactory::createOne(['companies' => [$this->company]]);
        self::assertInstanceOf(User::class, $user);
        $this->client->loginUser($user);

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $this->entityManager = $entityManager;

        $config = self::getContainer()->get(SystemConfig::class);
        $config->set(SystemConfig::CURRENCY_CONFIG_PATH, 'EUR');
        $config->set(AccountingSettings::REGIME, 'fr_micro');
        $config->set(AccountingSettings::PRIMARY_ACTIVITY, ActivityNature::ServicesBnc->value);
        $config->set(AccountingSettings::DECLARATION_PERIODICITY, PeriodType::Quarter->value);
    }

    public function testTheListShowsEveryPeriodOfTheYearIncludingUndeclaredOnes(): void
    {
        $this->entry();

        $crawler = $this->client->request('GET', '/accounting/declarations');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $text = $crawler->filter('body')->text();

        self::assertStringContainsString(new DateTimeImmutable('today')->format('Y') . '-Q', $text);
        self::assertStringContainsString('Not computed', $text, 'A period nobody has declared has to be visible as such.');
    }

    /**
     * The figures are itemised because the user has to type them into someone
     * else's form, box by box — and has to be able to see which rate produced
     * each one.
     */
    public function testADeclarationItemisesTheChargesWithTheirBaseAndRate(): void
    {
        $this->entry();
        $period = $this->period();

        $crawler = $this->client->request('GET', '/accounting/declarations/' . $period->getId());

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $text = $crawler->filter('.card-table')->text();

        // 10 000 EUR of BNC services at the SSI rate in force at the end of the
        // period, plus the training levy.
        self::assertStringContainsString('Social contributions', $text);
        self::assertStringContainsString('26.1%', $text);
        self::assertStringContainsString('Professional training levy', $text);
    }

    public function testAnOpenPeriodProducesADraftAndOffersNoFilingForm(): void
    {
        $this->entry();
        $period = $this->period();

        $crawler = $this->client->request('GET', '/accounting/declarations/' . $period->getId());

        self::assertSame(DeclarationStatus::Draft, $this->declaration()->getStatus());
        self::assertCount(0, $crawler->filter('form[action*="/submit"]'), 'Neither return can be filed while the period is open.');
    }

    public function testClosingThePeriodMakesTheDeclarationReadyToFile(): void
    {
        $this->entry();
        $period = $this->period();

        // Closed as of the day after it ends: a period still running cannot be
        // sealed, and the injectable clock is what lets this test say "later"
        // without waiting for the quarter to be over.
        self::getContainer()->get(AccountingPeriodManager::class)
            ->close($period, null, $period->getEndDate()->modify('+1 day'));

        $this->client->request('GET', '/accounting/declarations/' . $period->getId());

        $declaration = $this->declaration();

        self::assertSame(DeclarationStatus::Ready, $declaration->getStatus());
        self::assertSame((string) self::TURNOVER, (string) $declaration->getTotalTurnover());
        // 26.1% social + 0.2% training on 10 000 EUR, the 2026 SSI figures.
        self::assertSame('263000', (string) $declaration->getTotalDue());
    }

    public function testRecordingTheFilingFreezesTheDeclaration(): void
    {
        $this->entry();
        $period = $this->period();
        // Closed as of the day after it ends: a period still running cannot be
        // sealed, and the injectable clock is what lets this test say "later"
        // without waiting for the quarter to be over.
        self::getContainer()->get(AccountingPeriodManager::class)
            ->close($period, null, $period->getEndDate()->modify('+1 day'));

        $crawler = $this->client->request('GET', '/accounting/declarations/' . $period->getId());

        $this->client->submit($crawler->filter('form[action*="/submit/social_contributions"]')->form([
            'reference' => 'URSSAF-2026-001',
            'notes' => 'Paid by direct debit',
        ]));

        self::assertSame(302, $this->client->getResponse()->getStatusCode());

        $declaration = $this->declaration();

        self::assertSame(DeclarationStatus::Submitted, $declaration->getStatus());
        self::assertSame('URSSAF-2026-001', $declaration->getReference());
        self::assertFalse($declaration->isRecomputable());

        // Books that change afterwards must not rewrite what was filed.
        $this->entry();
        $this->client->request('GET', '/accounting/declarations/' . $period->getId());

        self::assertSame((string) self::TURNOVER, (string) $this->declaration()->getTotalTurnover());
    }

    /**
     * The nil return, end to end: a quarter nothing was booked into does not
     * exist, so it is created, closed and recorded as filed like any other —
     * and what it leaves behind is a declaration at zero with the reference the
     * authority gave back. A nil return that left no trace would be
     * indistinguishable from one nobody filed.
     */
    public function testAQuarterWithNothingInItCanBeCreatedClosedAndRecordedAsFiled(): void
    {
        // Trading since the start of the previous quarter, with the only entry
        // in the current one: the quarter in between has no row at all, which
        // is the whole situation being tested.
        $previousQuarter = PeriodType::Quarter->startOf(
            PeriodType::Quarter->startOf(new DateTimeImmutable('today'))->modify('-1 day'),
        );

        self::getContainer()->get(SystemConfig::class)
            ->set(AccountingSettings::ACTIVITY_START_DATE, $previousQuarter->format('Y-m-d'));

        $this->entry();

        // The gap, and the button that brings it into being.
        $crawler = $this->client->request('GET', '/accounting/declarations');
        $create = $crawler->filter('form[action*="/period/create"]');

        self::assertCount(1, $create, 'The quarter with nothing in it has to be offered.');

        $this->client->submit($create->form());
        $this->client->followRedirect();

        $period = $this->quarterStartingOn($previousQuarter);

        self::assertSame(PeriodStatus::Open, $period->getStatus());
        self::assertSame(0, $period->getEntryCount());

        // Closed through the page, with no clock injected: the quarter is over,
        // so the ordinary rule already allows it.
        $crawler = $this->client->request('GET', '/accounting/declarations/' . $period->getId());
        $this->client->submit($crawler->filter('form[action*="/close"]')->form());

        $crawler = $this->client->followRedirect();

        // Filed, with the reference the authority handed back.
        $this->client->submit($crawler->filter('form[action*="/submit"]')->form([
            'reference' => 'URSSAF-2026-NIL',
            'notes' => 'Nothing received this quarter',
        ]));

        self::assertSame(302, $this->client->getResponse()->getStatusCode());

        $declaration = $this->declarationFor($this->quarterStartingOn($previousQuarter));

        self::assertSame(DeclarationStatus::Submitted, $declaration->getStatus());
        self::assertSame('0', (string) $declaration->getTotalTurnover());
        self::assertSame('0', (string) $declaration->getTotalDue());
        self::assertSame('URSSAF-2026-NIL', $declaration->getReference());
        self::assertInstanceOf(DateTimeImmutable::class, $declaration->getSubmittedAt());
        self::assertFalse($declaration->isRecomputable());

        // And the page stops asking: the gap is a row now, and a filed one.
        $text = $this->client->request('GET', '/accounting/declarations')->filter('body')->text();

        self::assertCount(0, $this->client->getCrawler()->filter('form[action*="/period/create"]'));
        self::assertStringContainsString('Filed', $text, 'The nil quarter is a filed row now, not a gap.');
    }

    private function entry(): void
    {
        $entry = new LedgerEntry()
            ->setBook(LedgerBook::Revenue)
            ->setEntryDate(new DateTimeImmutable('today'))
            ->setLabel('Consulting work')
            ->setCounterpartyName('Johnston PLC')
            ->setAmount(BigInteger::of(self::TURNOVER))
            ->setCurrencyCode('EUR')
            ->setActivityNature(ActivityNature::ServicesBnc);

        $company = $this->entityManager->find(Company::class, $this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $entry->setCompany($company);

        self::getContainer()->get(AccountingPeriodManager::class)->assignPeriod($entry, PeriodType::Quarter);

        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    private function period(): AccountingPeriod
    {
        $entries = self::getContainer()->get(LedgerEntryRepository::class)->findAll();
        $period = $entries[0]->getPeriod();

        self::assertInstanceOf(AccountingPeriod::class, $period);

        return $period;
    }

    private function quarterStartingOn(DateTimeImmutable $start): AccountingPeriod
    {
        $this->entityManager->clear();

        foreach (self::getContainer()->get(AccountingPeriodRepository::class)->findAll() as $period) {
            if ($period->getStartDate()->format('Y-m-d') === $start->format('Y-m-d')) {
                return $period;
            }
        }

        self::fail('No period starting on ' . $start->format('Y-m-d') . ' was created.');
    }

    private function declarationFor(AccountingPeriod $period): Declaration
    {
        $declaration = self::getContainer()->get(DeclarationRepository::class)->findForPeriod($period);

        self::assertInstanceOf(Declaration::class, $declaration);

        return $declaration;
    }

    /**
     * The turnover declaration specifically. A period in the scope of VAT owes
     * a second one, and these tests are about the first.
     */
    private function declaration(): Declaration
    {
        $this->entityManager->clear();

        $declarations = self::getContainer()->get(DeclarationRepository::class)
            ->findBy(['kind' => DeclarationKind::SocialContributions]);

        self::assertCount(1, $declarations);

        return $declarations[0];
    }
}
