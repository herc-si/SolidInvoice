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

namespace Augias\InstallBundle\Tests\Form\Step;

use Augias\CoreBundle\Tests\FormTestCase;
use Augias\InstallBundle\Form\Step\StartStep;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(StartStep::class)]
final class StartStepTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $form = $this->factory->create(StartStep::class);

        // StartStep is an empty form, so it should just work with empty data
        $form->submit([]);

        self::assertTrue($form->isSynchronized());
        // Empty forms return an empty array, not null
        self::assertIsArray($form->getData());
    }

    public function testFormView(): void
    {
        $form = $this->factory->create(StartStep::class);
        $view = $form->createView();

        self::assertEmpty($view->children);
    }
}
