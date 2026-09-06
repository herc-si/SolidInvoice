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

namespace Augias\TaxBundle\Form\Type;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

/**
 * @extends AbstractType<mixed>
 */
final class TaxIdentifierCollectionType extends AbstractType
{
    #[Override]
    public function getParent(): string
    {
        return CollectionType::class;
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'tax_identifiers';
    }
}
