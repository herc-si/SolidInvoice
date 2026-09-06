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

namespace Augias\ClientBundle\Tests\Search;

use Augias\ClientBundle\Search\ClientResultFormatter;
use Augias\CoreBundle\Search\QualifiedResultFormatterInterface;
use Augias\CoreBundle\Search\ResultFormatterInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

final class ClientResultFormatterTest extends TestCase
{
    private Stub & RouterInterface $router;

    private ClientResultFormatter $formatter;

    protected function setUp(): void
    {
        $this->router = $this->createStub(RouterInterface::class);
        $this->formatter = new ClientResultFormatter($this->router);
    }

    public function testImplementsResultFormatterInterface(): void
    {
        self::assertInstanceOf(ResultFormatterInterface::class, $this->formatter);
    }

    public function testImplementsQualifiedResultFormatterInterface(): void
    {
        self::assertInstanceOf(QualifiedResultFormatterInterface::class, $this->formatter);
    }

    public function testGetIndexNameReturnsClients(): void
    {
        self::assertSame('clients', $this->formatter->getIndexName());
    }

    public function testGetSupportedQualifiersReturnsStatusMapping(): void
    {
        self::assertSame(['status' => 'status'], $this->formatter->getSupportedQualifiers());
    }

    public function testFormatMapsHitToSearchResult(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with('_clients_view', ['id' => 'client-id-1'])
            ->willReturn('/clients/client-id-1');

        $formatter = new ClientResultFormatter($router);

        $hit = [
            'id' => 'client-id-1',
            'name' => 'Acme Corp',
            'website' => 'https://acme.example.com',
            'status' => 'active',
        ];

        $result = $formatter->format($hit);

        self::assertSame('client', $result->type);
        self::assertSame('client-id-1', $result->id);
        self::assertSame('Acme Corp', $result->title);
        self::assertSame('https://acme.example.com', $result->subtitle);
        self::assertSame('/clients/client-id-1', $result->url);
        self::assertSame('active', $result->status);
        self::assertNull($result->meta);
    }

    public function testFormatWithMissingNameFallsBackToEmptyString(): void
    {
        $this->router->method('generate')->willReturn('/clients/id1');

        $result = $this->formatter->format(['id' => 'id1']);

        self::assertSame('', $result->title);
    }

    public function testFormatWithMissingWebsiteFallsBackToEmptyString(): void
    {
        $this->router->method('generate')->willReturn('/clients/id1');

        $result = $this->formatter->format(['id' => 'id1', 'name' => 'Acme']);

        self::assertSame('', $result->subtitle);
    }

    public function testFormatWithMissingStatusResultsInNullStatus(): void
    {
        $this->router->method('generate')->willReturn('/clients/id1');

        $result = $this->formatter->format(['id' => 'id1', 'name' => 'Acme']);

        self::assertNull($result->status);
    }

    public function testFormatGeneratesCorrectRouteWithClientId(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with('_clients_view', ['id' => 'abc-123'])
            ->willReturn('/clients/abc-123');

        $formatter = new ClientResultFormatter($router);
        $formatter->format(['id' => 'abc-123']);
    }

    public function testMetaIsAlwaysNull(): void
    {
        $this->router->method('generate')->willReturn('/clients/id1');

        $result = $this->formatter->format(['id' => 'id1', 'name' => 'Acme', 'status' => 'active']);

        self::assertNull($result->meta);
    }
}
