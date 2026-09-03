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

namespace SolidInvoice\ElectronicInvoicingBundle\Provider\SuperPdp;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use function is_int;
use function is_string;
use function sprintf;

/**
 * Thin wrapper around SUPER PDP's REST API (https://api.superpdp.tech,
 * documented at https://www.superpdp.tech/openapi/#superpdp).
 *
 * Auth is OAuth2 client_credentials: a fresh token is requested for every
 * call rather than cached, since electronic-invoice submissions/status
 * checks are low-frequency operations for a self-hosted invoicing app.
 *
 * @see \SolidInvoice\ElectronicInvoicingBundle\Tests\Provider\SuperPdp\SuperPdpClientTest
 */
final readonly class SuperPdpClient
{
    private const string BASE_URL = 'https://api.superpdp.tech';

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @throws SuperPdpApiException
     */
    public function getAccessToken(string $clientId, string $clientSecret): string
    {
        $response = $this->request('POST', '/oauth2/token', [
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ],
        ]);

        $token = $response['access_token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new SuperPdpApiException('SUPER PDP did not return an access token.');
        }

        return $token;
    }

    /**
     * Uploads a Factur-X invoice (or CII/UBL XML) for asynchronous processing.
     *
     * $processingRule is left null by default: SUPER PDP computes it (B2B, B2C,
     * B2BInt, ...) from the document itself, and only errors out if we pass a
     * value that disagrees with what it computed — since we can't reliably
     * predict that computation ourselves (it depends on directory lookups on
     * their end), it's safer to let them decide than to force one.
     *
     * @return array<string, mixed> the decoded invoice_overview response, notably its `id`
     *
     * @throws SuperPdpApiException
     */
    public function sendInvoice(string $accessToken, string $documentContent, string $externalId, ?string $processingRule = null): array
    {
        $query = ['external_id' => $externalId];

        if ($processingRule !== null) {
            $query['processing_rule'] = $processingRule;
        }

        return $this->request('POST', '/v1.beta/invoices', [
            'auth_bearer' => $accessToken,
            'headers' => ['Content-Type' => 'application/pdf'],
            'query' => $query,
            'body' => $documentContent,
        ]);
    }

    /**
     * @return array<string, mixed> the decoded invoice_overview response, notably its `events`
     *
     * @throws SuperPdpApiException
     */
    public function getInvoice(string $accessToken, string $superPdpInvoiceId): array
    {
        return $this->request('GET', '/v1.beta/invoices/' . $superPdpInvoiceId, [
            'auth_bearer' => $accessToken,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     *
     * @throws SuperPdpApiException
     */
    private function request(string $method, string $path, array $options): array
    {
        try {
            $response = $this->httpClient->request($method, self::BASE_URL . $path, $options);

            // Default $throw=true: a 4xx/5xx must raise HttpExceptionInterface here so
            // extractError() below can surface the API's http_ko message, instead of
            // silently returning its error body as if it were a successful response.
            return $response->toArray();
        } catch (TransportException $e) {
            throw new SuperPdpApiException('Could not reach the SUPER PDP API: ' . $e->getMessage(), previous: $e);
        } catch (ExceptionInterface $e) {
            [$message, $code] = $this->extractError($e);

            throw new SuperPdpApiException($message, $code, $e);
        }
    }

    /**
     * @return array{0: string, 1: ?int}
     */
    private function extractError(ExceptionInterface $e): array
    {
        if (! $e instanceof HttpExceptionInterface) {
            return [$e->getMessage(), null];
        }

        try {
            $content = $e->getResponse()->toArray(false);
        } catch (Throwable) {
            return [$e->getMessage(), null];
        }

        $message = is_string($content['message'] ?? null) ? $content['message'] : $e->getMessage();
        $code = is_int($content['code'] ?? null) ? $content['code'] : null;

        return [$code !== null ? sprintf('%s [%d]', $message, $code) : $message, $code];
    }
}
