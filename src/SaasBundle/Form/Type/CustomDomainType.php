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

namespace Augias\SaasBundle\Form\Type;

use Augias\CoreBundle\Validator\Constraints\NotApplicationUrlHost;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Hostname;

/**
 * @extends AbstractType<mixed>
 */
final class CustomDomainType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new Hostname(requireTld: true),
                new NotApplicationUrlHost(),
            ],
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return TextType::class;
    }
}
