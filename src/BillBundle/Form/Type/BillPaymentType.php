<?php

declare(strict_types=1);

/*
 * This file is part of Augias project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Augias\BillBundle\Form\Type;

use Augias\BillBundle\Entity\BillPayment;
use Augias\BillBundle\Enum\BillPaymentMethod;
use Brick\Math\BigNumber;
use Money\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @see \Augias\BillBundle\Tests\Form\Type\BillPaymentTypeTest
 * @extends AbstractType<BillPayment>
 */
final class BillPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'bill.payment.form.amount.label',
                'currency' => $options['currency'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Callback(function (BigNumber $value, ExecutionContextInterface $context): void {
                        if ($value->isZero() || $value->isNegative()) {
                            $context->buildViolation('bill.payment.form.amount.positive')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('paidDate', DateType::class, [
                'label' => 'bill.payment.form.paid_date.label',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('method', EnumType::class, [
                'label' => 'bill.payment.form.method.label',
                'class' => BillPaymentMethod::class,
                'choice_label' => static fn (BillPaymentMethod $method) => $method->getLabel(),
                'placeholder' => false,
            ])
            ->add('reference', null, ['label' => 'bill.payment.form.reference.label', 'required' => false])
            ->add('notes', null, ['label' => 'bill.payment.form.notes.label', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BillPayment::class,
            'currency' => new Currency('EUR'),
        ]);

        $resolver->setAllowedTypes('currency', Currency::class);
    }

    public function getBlockPrefix(): string
    {
        return 'bill_payment';
    }
}
