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

namespace Augias\BillBundle\Tests\Dashboard;

use Augias\BillBundle\Dashboard\PurchasesWidget;
use Augias\BillBundle\Entity\Bill;
use Augias\BillBundle\Entity\BillPayment;
use Augias\BillBundle\Enum\BillPaymentMethod;
use Augias\BillBundle\Enum\BillStatus;
use Augias\ClientBundle\Entity\Client;
use Augias\InstallBundle\Test\EnsureApplicationInstalled;
use Brick\Math\BigInteger;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

#[CoversClass(PurchasesWidget::class)]
final class PurchasesWidgetTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use MatchesSnapshots;

    private EntityManagerInterface $entityManager;

    private ?Client $supplier = null;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $this->supplier = null;
    }

    public function testGetDataReturnsCorrectStructure(): void
    {
        $data = $this->widget()->getData();

        self::assertArrayHasKey('outstanding', $data);
        self::assertArrayHasKey('bills', $data);
        self::assertArrayHasKey('unpaidTotal', $data);
        self::assertArrayHasKey('overdueTotal', $data);
        self::assertArrayHasKey('hasBills', $data);
    }

    public function testGetDataWithNoBills(): void
    {
        $data = $this->widget()->getData();

        self::assertSame([], $data['outstanding']);
        self::assertSame([], $data['bills']);
        self::assertFalse($data['hasBills']);
    }

    /**
     * A draft is a bill somebody has typed in and not confirmed. It is not money
     * owed yet, so it stays out of the total and off the list.
     */
    public function testDraftBillsAreNotOwedYet(): void
    {
        $this->bill(BillStatus::Draft, 10_000);

        $data = $this->widget()->getData();

        self::assertSame([], $data['outstanding']);
        self::assertFalse($data['hasBills']);
    }

    public function testPaidAndCancelledBillsAreNotOwedEither(): void
    {
        $this->bill(BillStatus::Paid, 10_000);
        $this->bill(BillStatus::Cancelled, 20_000);

        self::assertSame([], $this->widget()->getData()['outstanding']);
    }

    public function testSumsWhatIsStillOwed(): void
    {
        $this->bill(BillStatus::Pending, 10_000);
        $this->bill(BillStatus::Overdue, 5_000);

        $data = $this->widget()->getData();

        self::assertSame('15000', (string) $data['outstanding']['EUR']);
        self::assertSame(2, $data['unpaidTotal']);
        self::assertSame(1, $data['overdueTotal']);
    }

    /**
     * The total is what is left to pay, not what was billed: a part-paid bill
     * that still showed its full amount would overstate the debt.
     */
    public function testPaymentsAreSubtractedFromTheTotal(): void
    {
        $bill = $this->bill(BillStatus::Pending, 10_000);
        $this->payment($bill, 4_000);

        self::assertSame('6000', (string) $this->widget()->getData()['outstanding']['EUR']);
    }

    /**
     * Two payments against one bill must not multiply that bill's own total —
     * the fan-out this repository uses two queries to avoid.
     */
    public function testSeveralPaymentsAgainstOneBillDoNotInflateIt(): void
    {
        $bill = $this->bill(BillStatus::Pending, 10_000);
        $this->payment($bill, 3_000);
        $this->payment($bill, 2_000);

        self::assertSame('5000', (string) $this->widget()->getData()['outstanding']['EUR']);
    }

    /**
     * Overpaid is not negative money owed, and the total has to agree with the
     * rows the user can see under it — Bill::getBalance() floors at zero too.
     */
    public function testAnOverpaidBillIsNotNegativeDebt(): void
    {
        $bill = $this->bill(BillStatus::Pending, 10_000);
        $this->payment($bill, 12_000);

        self::assertSame([], $this->widget()->getData()['outstanding']);
    }

    /**
     * Bills are counted in their own currency and never converted: an exchange
     * rate invented on a dashboard would be one the books never recorded.
     */
    public function testCurrenciesAreKeptApart(): void
    {
        $this->bill(BillStatus::Pending, 10_000, currency: 'EUR');
        $this->bill(BillStatus::Pending, 7_000, currency: 'USD');

        $outstanding = $this->widget()->getData()['outstanding'];

        self::assertSame('10000', (string) $outstanding['EUR']);
        self::assertSame('7000', (string) $outstanding['USD']);
    }

    /**
     * A bill with no due date is owed but not late, and has no business sorting
     * ahead of one that is.
     */
    public function testDatedBillsComeBeforeUndatedOnes(): void
    {
        $this->bill(BillStatus::Pending, 1_000, dueDate: null, number: 'NO-DATE');
        $this->bill(BillStatus::Overdue, 2_000, dueDate: new DateTimeImmutable('2026-01-15'), number: 'LATE');

        $bills = $this->widget()->getData()['bills'];

        self::assertSame('LATE', $bills[0]->getBillNumber());
        self::assertSame('NO-DATE', $bills[1]->getBillNumber());
    }

    public function testGetTemplate(): void
    {
        self::assertSame('@AugiasBill/Widget/purchases.html.twig', $this->widget()->getTemplate());
    }

    public function testRenderWidgetWithNoData(): void
    {
        $this->assertMatchesHtmlSnapshot($this->render());
    }

    public function testRenderWidgetWithData(): void
    {
        $bill = $this->bill(BillStatus::Overdue, 25_000, dueDate: new DateTimeImmutable('2026-02-01'), number: 'SUP-0042');
        $this->payment($bill, 5_000);
        $this->bill(BillStatus::Pending, 8_000, dueDate: new DateTimeImmutable('2026-12-01'), number: 'SUP-0043');

        $this->assertMatchesHtmlSnapshot($this->render());
    }

    private function bill(
        BillStatus $status,
        int $amount,
        string $currency = 'EUR',
        ?DateTimeImmutable $dueDate = null,
        ?string $number = null,
    ): Bill {
        $bill = new Bill();
        $bill->setCompany($this->company)
            ->setSupplier($this->supplier())
            ->setBillNumber($number)
            ->setStatus($status)
            ->setDueDate($dueDate)
            ->setTotalAmount(BigInteger::of($amount))
            ->setCurrencyCode($currency);

        $this->entityManager->persist($bill);
        $this->entityManager->flush();

        return $bill;
    }

    /**
     * One supplier per test, reused.
     *
     * A client's name is unique per company, so a helper that made a fresh
     * "Acme Supplies" for every bill would fail on the second one — and several
     * bills from the same supplier is the ordinary case anyway.
     */
    private function supplier(): Client
    {
        if ($this->supplier instanceof Client) {
            return $this->supplier;
        }

        $supplier = new Client();
        $supplier->setCompany($this->company)
            ->setName('Acme Supplies')
            ->setIsClient(false)
            ->setIsSupplier(true);

        $this->entityManager->persist($supplier);
        $this->entityManager->flush();

        return $this->supplier = $supplier;
    }

    private function payment(Bill $bill, int $amount): void
    {
        $payment = new BillPayment();
        $payment->setCompany($this->company)
            ->setBill($bill)
            ->setAmount(BigInteger::of($amount))
            ->setCurrencyCode($bill->getCurrencyCode())
            ->setPaidDate(new DateTimeImmutable('2026-03-01'))
            ->setMethod(BillPaymentMethod::BankTransfer);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();
        $this->entityManager->refresh($bill);
    }

    private function render(): string
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $widget = $this->widget();

        return trim((string) preg_replace(
            '#[0-9A-HJKMNP-TV-Z]{26}#',
            '01JBYEQCR7DJ2YW4EXP6FYJZCR',
            $twig->render($widget->getTemplate(), $widget->getData()),
        ));
    }

    private function widget(): PurchasesWidget
    {
        $widget = self::getContainer()->get(PurchasesWidget::class);
        self::assertInstanceOf(PurchasesWidget::class, $widget);

        return $widget;
    }
}
