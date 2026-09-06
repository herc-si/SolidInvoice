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

namespace Augias\CoreBundle\Tests\Twig\Components;

use Augias\CoreBundle\Enum\CustomFieldTarget;
use Augias\CoreBundle\Repository\CustomFieldRepository;
use Augias\CoreBundle\Repository\CustomFieldValueRepository;
use Augias\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Augias\CoreBundle\Twig\Components\CustomFieldsList;
use Augias\CoreBundle\Twig\Components\CustomFieldsListPdf;
use PHPUnit\Framework\TestCase;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Uid\Ulid;

/**
 * Asserts that the admin and PDF view components return an empty row set when
 * the `custom_fields` feature is disabled, so no custom-field data leaks onto
 * surfaces the user no longer has access to.
 */
final class CustomFieldsListGateTest extends TestCase
{
    public function testAdminListReturnsEmptyWhenGated(): void
    {
        $fields = $this->createMock(CustomFieldRepository::class);
        $fields->expects(self::never())->method('findByTargetOrdered');

        $component = new CustomFieldsList(
            $fields,
            $this->createStub(CustomFieldValueRepository::class),
            new CustomFieldTypeResolver(),
            $this->buildGate(false),
        );

        $component->target = CustomFieldTarget::INVOICE;
        $component->recordId = new Ulid();

        self::assertSame([], $component->getRows());
    }

    public function testPdfListReturnsEmptyWhenGated(): void
    {
        $fields = $this->createMock(CustomFieldRepository::class);
        $fields->expects(self::never())->method('findByTargetOrdered');

        $component = new CustomFieldsListPdf(
            $fields,
            $this->createStub(CustomFieldValueRepository::class),
            new CustomFieldTypeResolver(),
            $this->buildGate(false),
        );

        $component->target = CustomFieldTarget::QUOTE;
        $component->recordId = new Ulid();

        self::assertSame([], $component->getRows());
    }

    private function buildGate(bool $enabled): FeatureGate
    {
        $gate = $this->createStub(FeatureGate::class);
        $gate->method('isEnabled')->willReturn($enabled);

        return $gate;
    }
}
