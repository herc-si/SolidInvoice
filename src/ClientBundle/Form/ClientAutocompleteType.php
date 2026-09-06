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

namespace Augias\ClientBundle\Form;

use Augias\ClientBundle\Entity\Client;
use Doctrine\ORM\EntityRepository;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

/**
 * @extends AbstractType<mixed>
 */
#[AsEntityAutocompleteField]
final class ClientAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Client::class,
            'searchable_fields' => ['name'],
            'choice_label' => 'name',
            // A pure supplier (isClient false) is never meant to be invoiced,
            // so it doesn't belong in the "choose a client" picker.
            'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('c')
                ->andWhere('c.isClient = true'),
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
