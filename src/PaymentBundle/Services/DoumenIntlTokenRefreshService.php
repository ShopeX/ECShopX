<?php

declare(strict_types=1);

namespace PaymentBundle\Services;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * 斗门国际网关：主动 GET /authorize 并将 token 写入与 {@see DoumenIntlGatewayClient} 相同的 Redis 缓存。
 */
final class DoumenIntlTokenRefreshService
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

    public function __construct(
        string $accessCode,
        string $secretKey,
        string $baseUri,
        ClientInterface $http,
        object $tokenStore
    ) {
        $this->accessCode = $accessCode;
        $this->secretKey = $secretKey;
        $this->baseUri = $baseUri;
        $this->http = $http;
        $this->tokenStore = $tokenStore;
    }

    public function refreshToken(): bool
    {
        try {
            $response = $this->http->request('GET', '/authorize', [
                'headers' => [
                    'X-AccessCode' => $this->accessCode,
                    'X-SecretKey' => $this->secretKey,
                ],
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        try {
            $data = $this->parseResponseData($response);
        } catch (RuntimeException $e) {
            return false;
        }

        $token = $data['token'] ?? null;
        $expireIn = $data['expireIn'] ?? null;
        if (! is_string($token) || $token === '' || ! is_int($expireIn)) {
            return false;
        }

        $ttlSeconds = max(1, $expireIn - self::TOKEN_TTL_SAFETY_MARGIN);
        $this->tokenStore->set($this->tokenCacheKey(), $token, $ttlSeconds);

        return true;
    }

    private function tokenCacheKey(): string
    {
        return self::TOKEN_KEY_PREFIX.$this->accessCode;
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
            app('log')->info('Doumen Intl gateway token refresh response', ['response' => $decoded]);
            throw new RuntimeException('Doumen Intl gateway business error: '.(string) $code);
        }

        $data = $decoded['data'] ?? null;
        if (! is_array($data)) {
            throw new RuntimeException('Doumen Intl response missing data.');
        }

        return $data;
    }
}
