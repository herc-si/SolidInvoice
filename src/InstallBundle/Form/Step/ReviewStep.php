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

namespace Augias\InstallBundle\Form\Step;

use Augias\InstallBundle\DTO\Installation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @see \Augias\InstallBundle\Tests\Form\Step\ReviewStepTest
 * @extends AbstractType<Installation>
 */
final class ReviewStep extends AbstractType
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('token', HiddenType::class, ['data' => (string) $this->csrfTokenManager->getToken('system_installation')]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Installation::class,
            'inherit_data' => true,
        ]);
    }
}
