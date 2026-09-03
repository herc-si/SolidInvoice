<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ElectronicInvoicingBundle\Tests\Provider\SuperPdp;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdp\SuperPdpApiException;
use SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdp\SuperPdpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use function json_encode;

#[CoversClass(SuperPdpClient::class)]
final class SuperPdpClientTest extends TestCase
{
    public function testGetAccessTokenReturnsTheToken(): void
    {
        $httpClient = new MockHttpClient(new MockResponse((string) json_encode(['access_token' => 'a-token', 'expires_in' => 3600])));

        $client = new SuperPdpClient($httpClient);

        self::assertSame('a-token', $client->getAccessToken('id', 'secret'));
    }

    public function testGetAccessTokenThrowsWhenTokenIsMissing(): void
    {
        $httpClient = new MockHttpClient(new MockResponse((string) json_encode(['token_type' => 'Bearer'])));

        $client = new SuperPdpClient($httpClient);

        $this->expectException(SuperPdpApiException::class);

        $client->getAccessToken('id', 'secret');
    }

    public function testSendInvoiceReturnsTheDecodedResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse((string) json_encode(['id' => 42, 'events' => []])));

        $client = new SuperPdpClient($httpClient);

        $response = $client->sendInvoice('a-token', '%PDF-1.7 ...', 'inv-1');

        self::assertSame(42, $response['id']);
    }

    public function testSendInvoiceThrowsWithTheApiErrorMessage(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            (string) json_encode(['code' => 12, 'http_status_code' => 400, 'message' => 'Invalid document']),
            ['http_code' => 400],
        ));

        $client = new SuperPdpClient($httpClient);

        try {
            $client->sendInvoice('a-token', 'garbage', 'inv-1');
            self::fail('Expected a SuperPdpApiException to be thrown.');
        } catch (SuperPdpApiException $e) {
            self::assertStringContainsString('Invalid document', $e->getMessage());
            self::assertSame(12, $e->getApiCode());
        }
    }

    public function testGetInvoiceReturnsTheDecodedResponse(): void
    {
        $httpClient = new MockHttpClient(new MockResponse((string) json_encode(['id' => 42, 'events' => [['status_code' => 'fr:200']]])));

        $client = new SuperPdpClient($httpClient);

        $response = $client->getInvoice('a-token', '42');

        self::assertSame('fr:200', $response['events'][0]['status_code']);
    }
}
