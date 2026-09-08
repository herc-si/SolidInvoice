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

namespace Augias\AccountingBundle\Form\Type;

use Augias\AccountingBundle\Entity\LedgerEntry;
use Augias\AccountingBundle\Enum\ActivityNature;
use Augias\AccountingBundle\Enum\LedgerBook;
use Augias\AccountingBundle\Enum\SettlementMethod;
use Augias\SettingsBundle\SystemConfig;
use Money\Currency;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form behind a hand-written book entry — money that moved without passing
 * through an invoice or a supplier bill, and corrections to what did.
 *
 * An entry the application wrote itself is shown here too, but only its
 * bookkeeping-side fields can be touched: the activity it counts towards, which
 * is a judgement the payment record cannot make, and the notes. Its date and
 * amount mirror a payment and would silently disagree with it if they could be
 * edited — the payment is the record, and this is its reflection.
 *
 * Nothing here decides whether the entry may be edited at all. A sealed entry
 * never reaches this form, and would be refused by the Doctrine listener even
 * if it did.
 *
 * @see \Augias\AccountingBundle\Tests\Form\Type\LedgerEntryTypeTest
 * @extends AbstractType<LedgerEntry>
 */
final class LedgerEntryType extends AbstractType
{
    public function __construct(
        private readonly SystemConfig $systemConfig,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mirrorsAPayment = $options['mirrors_a_payment'];

        $builder
            ->add('entryDate', DateType::class, [
                'label' => 'accounting.entry.form.entry_date',
                'help' => 'accounting.entry.form.entry_date_help',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'disabled' => $mirrorsAPayment,
            ])
            ->add('label', TextType::class, [
                'label' => 'accounting.entry.form.label',
                'disabled' => $mirrorsAPayment,
            ])
            ->add('counterpartyName', TextType::class, [
                'label' => 'accounting.entry.form.counterparty',
                'disabled' => $mirrorsAPayment,
            ])
            ->add('documentReference', TextType::class, [
                'label' => 'accounting.entry.form.document_reference',
                'help' => 'accounting.entry.form.document_reference_help',
                'required' => false,
                'disabled' => $mirrorsAPayment,
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'accounting.entry.form.amount',
                'currency' => $options['currency'],
                'disabled' => $mirrorsAPayment,
            ])
            ->add('settlementMethod', EnumType::class, [
                'label' => 'accounting.entry.form.settlement_method',
                'class' => SettlementMethod::class,
                'choice_label' => static fn (SettlementMethod $method): string => $method->translationKey(),
                'required' => false,
                'placeholder' => '',
                'disabled' => $mirrorsAPayment,
            ]);

        // Only revenue is split by activity: the ceilings and the contribution
        // rates are per activity, and nothing about a purchase is.
        if ($options['book'] === LedgerBook::Revenue) {
            $builder->add('activityNature', EnumType::class, [
                'label' => 'accounting.entry.form.activity_nature',
                'help' => 'accounting.entry.form.activity_nature_help',
                'class' => ActivityNature::class,
                'choice_label' => static fn (ActivityNature $nature): string => $nature->translationKey(),
                'required' => false,
                'placeholder' => '',
            ]);
        }

        $builder->add('notes', TextareaType::class, [
            'label' => 'accounting.entry.form.notes',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LedgerEntry::class,
            'book' => LedgerBook::Revenue,
            'mirrors_a_payment' => false,
            // The company's own currency: the money field scales by the
            // currency's decimal count, so the wrong one misplaces the decimal
            // point for JPY and BHD.
            'currency' => $this->systemConfig->getCurrency(),
        ]);

        $resolver->setAllowedTypes('book', LedgerBook::class);
        $resolver->setAllowedTypes('mirrors_a_payment', 'bool');
        $resolver->setAllowedTypes('currency', Currency::class);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'ledger_entry';
    }
}
