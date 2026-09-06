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

namespace Augias\PaymentBundle\Tests\Factory\DependencyInjection;

use Augias\PaymentBundle\DependencyInjection\Configuration;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;
    use MockeryPHPUnitIntegration;

    protected function getConfiguration()
    {
        return new Configuration();
    }

    public function testValidConfig(): void
    {
        $this->assertConfigurationIsValid(
            [
                'payment' => [
                    'gateways' => [
                        'one' => [
                            'name' => 'one',
                            'factory' => 'one',
                            'form' => 'two',
                        ],
                    ],
                ],
            ]
        );
    }

    public function testFormIsOptionalConfig(): void
    {
        $this->assertConfigurationIsValid(
            [
                'payment' => [
                    'gateways' => [
                        'one' => [
                            'name' => 'one',
                            'factory' => 'one',
                        ],
                    ],
                ],
            ]
        );
    }

    public function testNoGatewaysConfigured(): void
    {
        $this->assertConfigurationIsInvalid(
            [
                'payment' => [
                    'gateways' => [],
                ],
            ],
            // 'The path "payment.gateways" should have at least 1 element(s) defined.'
        );
    }

    public function testFactoryConfigCannotBeEmpty(): void
    {
        $this->assertConfigurationIsInvalid(
            [
                'payment' => [
                    'gateways' => [
                        'one' => [
                            'name' => 'one',
                            'factory' => '',
                        ],
                    ],
                ],
            ],
            // 'The path "payment.gateways.one.factory" cannot contain an empty value, but got "".'
        );
    }

    public function testFormConfigCannotBeEmpty(): void
    {
        $this->assertConfigurationIsInvalid(
            [
                'payment' => [
                    'gateways' => [
                        'one' => [
                            'name' => 'one',
                            'factory' => 'one',
                            'form' => null,
                        ],
                    ],
                ],
            ],
            // 'The path "payment.gateways.one.form" cannot contain an empty value, but got null.'
        );
    }
}
