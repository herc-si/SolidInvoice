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

namespace Augias\DataGridBundle\Tests\GridBuilder\Formatter;

use Augias\DataGridBundle\GridBuilder\Column\UrlColumn;
use Augias\DataGridBundle\GridBuilder\Formatter\UrlFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[CoversClass(UrlFormatter::class)]
final class UrlFormatterTest extends TestCase
{
    private UrlFormatter $formatter;

    protected function setUp(): void
    {
        $twig = new Environment(new ArrayLoader());
        $this->formatter = new UrlFormatter($twig);
    }

    public function testFormatReturnsCorrectUrlForStringValue(): void
    {
        $column = UrlColumn::new('url');

        self::assertSame('<a href="https://example.com" target="_blank">https://example.com</a>', $this->formatter->format($column, 'https://example.com'));
    }

    public function testFormatReturnsEmptyUrlForNullValue(): void
    {
        $column = UrlColumn::new('url');

        self::assertSame('<a href="" target="_blank"></a>', $this->formatter->format($column, null));
    }
}
