<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\BillBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Money\Currency;
use SolidInvoice\BillBundle\Entity\Bill;
use SolidInvoice\BillBundle\Entity\BillCategory;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\BillBundle\Tests\Form\Type\BillTypeTest
 * @extends AbstractType<Bill>
 */
final class BillType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $bill = $options['data'] ?? null;
        $existingSupplier = $bill instanceof Bill && $bill->hasSupplier() ? $bill->getSupplier() : null;

        $builder
            // Unmapped: a brand new supplier can be typed into `newSupplierName`
            // below instead of picked here, so the Action resolves the two into
            // a single `Client` and calls `setSupplier()` itself rather than
            // letting the form's automatic property mapping do it — mapping
            // would otherwise try `setSupplier(null)` (a TypeError, the
            // property isn't nullable) whenever the "new supplier" path is used.
            ->add('supplier', EntityType::class, [
                'class' => Client::class,
                'label' => 'bill.form.supplier.label',
                'choice_label' => 'name',
                'required' => false,
                'mapped' => false,
                'data' => $existingSupplier,
                'placeholder' => 'bill.form.supplier.placeholder',
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('c')
                    ->andWhere('c.isSupplier = true'),
            ])
            ->add('newSupplierName', TextType::class, [
                'label' => 'bill.form.new_supplier_name.label',
                'help' => 'bill.form.new_supplier_name.help',
                'required' => false,
                'mapped' => false,
            ])
            ->add('billNumber', null, ['label' => 'bill.form.bill_number.label', 'required' => false])
            ->add('issueDate', DateType::class, [
                'label' => 'bill.form.issue_date.label',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'bill.form.due_date.label',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('currencyCode', CurrencyType::class, ['label' => 'bill.form.currency.label'])
            ->add('totalAmount', MoneyType::class, [
                'label' => 'bill.form.total_amount.label',
                'currency' => $options['currency'],
            ])
            ->add('category', EntityType::class, [
                'class' => BillCategory::class,
                'label' => 'bill.form.category.label',
                'required' => false,
                'placeholder' => 'bill.form.category.placeholder',
                'choice_label' => 'name',
            ])
            ->add('notes', TextareaType::class, ['label' => 'bill.form.notes.label', 'required' => false]);

        // `supplier` was made optional so a brand new supplier can be typed
        // into `newSupplierName` instead of picked from the list (handled by
        // the Action once the form is valid) — but exactly one of the two is
        // required, which plain field-level constraints can't express.
        $builder->addEventListener(FormEvents::SUBMIT, static function (FormEvent $event): void {
            $form = $event->getForm();

            if ($form->get('supplier')->getData() === null && trim((string) $form->get('newSupplierName')->getData()) === '') {
                $form->get('supplier')->addError(new FormError('bill.constraint.supplier_required'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bill::class,
            'currency' => new Currency('EUR'),
        ]);

        $resolver->setAllowedTypes('currency', Currency::class);
    }

    public function getBlockPrefix(): string
    {
        return 'bill';
    }
}
