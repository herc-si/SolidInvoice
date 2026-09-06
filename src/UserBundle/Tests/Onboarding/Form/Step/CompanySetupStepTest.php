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

namespace Augias\UserBundle\Tests\Onboarding\Form\Step;

use Augias\CoreBundle\Tests\FormTestCase;
use Augias\UserBundle\Onboarding\Form\Step\CompanySetupStep;

final class CompanySetupStepTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'test' => [
                'companyName' => 'Acme Corporation',
                'companyCurrency' => 'USD',
            ],
        ];

        $this->assertFormData($this->factory->createNamed('test')->add('test', CompanySetupStep::class), $formData, $formData);
    }

    public function testSubmitWithoutData(): void
    {
        $formData = [
            'test' => [
                'companyName' => '',
                'companyCurrency' => '',
            ],
        ];

        $this->assertFormData($this->factory->createNamed('test')->add('test', CompanySetupStep::class), $formData, $formData);
    }
}
