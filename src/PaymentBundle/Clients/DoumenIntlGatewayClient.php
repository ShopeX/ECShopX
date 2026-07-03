<?php

declare(strict_types=1);

namespace PaymentBundle\Clients;

use GuzzleHttp\ClientInterface;
use PaymentBundle\Support\DoumenIntlSignature;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class DoumenIntlGatewayClient
{
    private const TOKEN_KEY_PREFIX = 'doumen_intl:token:';

    private const TOKEN_TTL_SAFETY_MARGIN = 60;

    private const SUCCESS_CODE = '00000000';

    private string $accessCode;

    private string $secretKey;

    private string $baseUri;

    private ClientInterface $http;

    /** @var object{get(string): ?array{token: string, expires_at: int}, set(string, string, int): void} */
    private object $tokenStore;

    private DoumenIntlSignature $signer;

    public function __construct(
        string $accessCode,
        string $secretKey,
        string $baseUri,
        ClientInterface $http,
        object $tokenStore,
        ?DoumenIntlSignature $signer = null
    ) {
        $this->accessCode = $accessCode;
        $this->secretKey = $secretKey;
        $this->baseUri = $baseUri;
        $this->http = $http;
        $this->tokenStore = $tokenStore;
        $this->signer = $signer ?? new DoumenIntlSignature();
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function createCheckout(array $body): array
    {
        $token = $this->resolveToken();
        $jsonBody = $this->encodeJson($body);
        $signature = $this->signer->signPostBody($jsonBody, $this->secretKey);

        app('log')->info('Doumen Intl gateway create checkout data', ['data' => [
            'headers' => $this->businessHeaders($token, $signature),
            'body' => $jsonBody,
        ]]);

        $response = $this->http->request('POST', '/api/acquire/checkout/create', [
            'headers' => $this->businessHeaders($token, $signature),
            'body' => $jsonBody,
        ]);

        return $this->parseResponseData($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function queryPayment(string $transactionId): array
    {
        $token = $this->resolveToken();

        $response = $this->http->request('GET', '/api/acquire/payment/'.$transactionId.'/get', [
            'headers' => $this->businessHeaders($token),
        ]);

        return $this->parseResponseData($response);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function refund(string $originalId, array $body): array
    {
        $result = $this->refundWithResult($originalId, $body);
        if (! $result['ok']) {
            app('log')->info('Doumen Intl gateway refundWithResult response', ['response' => $result]);
            throw new RuntimeException('Doumen Intl gateway business error: '.($result['code'] ?? ''));
        }

        return $result['data'];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{ok: true, data: array<string, mixed>}|array{ok: false, code: string, message: string}
     */
    public function refundWithResult(string $originalId, array $body): array
    {
        $token = $this->resolveToken();
        $jsonBody = $this->encodeJson($body);
        $signature = $this->signer->signPostBody($jsonBody, $this->secretKey);

        $response = $this->http->request('POST', '/api/acquire/payment/'.$originalId.'/refund', [
            'headers' => $this->businessHeaders($token, $signature),
            'body' => $jsonBody,
        ]);

        return $this->parseRefundResponse($response);
    }

    /**
     * @return array{ok: true, data: array<string, mixed>}|array{ok: false, code: string, message: string}
     */
    private function parseRefundResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Doumen Intl response is not valid JSON.');
        }

        $code = (string) ($decoded['code'] ?? '');
        if ($code !== self::SUCCESS_CODE) {
            return [
                'ok' => false,
                'code' => $code,
                'message' => (string) ($decoded['message'] ?? ''),
            ];
        }

        $data = $decoded['data'] ?? null;
        if (! is_array($data)) {
            throw new RuntimeException('Doumen Intl response missing data.');
        }

        return [
            'ok' => true,
            'data' => $data,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function businessHeaders(string $token, ?string $signature = null): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$token,
            'X-AccessCode' => $this->accessCode,
        ];

        if ($signature !== null) {
            $headers['X-Signature'] = $signature;
            $headers['Content-Type'] = 'application/json; charset=utf-8';
        }

        return $headers;
    }

    private function resolveToken(): string
    {
        $cacheKey = $this->tokenCacheKey();
        $cached = $this->tokenStore->get($cacheKey);
        if (is_array($cached) && isset($cached['token']) && is_string($cached['token']) && $cached['token'] !== '') {
            return $cached['token'];
        }

        return $this->authorize();
    }

    private function authorize(): string
    {
        try {
            $response = $this->http->request('GET', '/authorize', [
                'headers' => [
                    'X-AccessCode' => $this->accessCode,
                    'X-SecretKey' => $this->secretKey,
                ],
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Doumen Intl gateway authentication failed: '.$e->getMessage(), 0, $e);
        }

        try {
            $data = $this->parseResponseData($response);
        } catch (RuntimeException $e) {
            throw new RuntimeException('Doumen Intl gateway authentication failed: '.$e->getMessage(), 0, $e);
        }

        $token = $data['token'] ?? null;
        $expireIn = $data['expireIn'] ?? null;
        if (! is_string($token) || $token === '' || ! is_int($expireIn)) {
            throw new RuntimeException('Doumen Intl gateway authentication failed: authorize response missing token or expireIn.');
        }

        $ttlSeconds = max(1, $expireIn - self::TOKEN_TTL_SAFETY_MARGIN);
        $this->tokenStore->set($this->tokenCacheKey(), $token, $ttlSeconds);

        return $token;
    }

    private function tokenCacheKey(): string
    {
        return self::TOKEN_KEY_PREFIX.$this->accessCode;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function encodeJson(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Failed to encode Doumen Intl request body.');
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponseData(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Doumen Intl response is not valid JSON.');
        }

        $code = $decoded['code'] ?? null;
        if ($code !== self::SUCCESS_CODE) {
            app('log')->info('Doumen Intl gateway parse response data response', ['response' => $decoded]);
            throw new RuntimeException('Doumen Intl gateway business error: '.(string) $code);
        }

        $data = $decoded['data'] ?? null;
        if (! is_array($data)) {
            throw new RuntimeException('Doumen Intl response missing data.');
        }

        return $data;
    }
}
