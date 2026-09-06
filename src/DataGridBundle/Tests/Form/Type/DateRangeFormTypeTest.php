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

namespace Augias\DataGridBundle\Tests\Form\Type;

use Augias\CoreBundle\Tests\FormTestCase;
use Augias\DataGridBundle\Form\Type\DateRangeFormType;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DateRangeFormType::class)]
final class DateRangeFormTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'start' => '2020-01-01',
            'end' => '2020-01-31',
        ];

        $form = $this->factory->create(DateRangeFormType::class);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSubmitted());
        self::assertSame($formData, $form->getData());
    }
}
