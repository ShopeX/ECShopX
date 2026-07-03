<?php

declare(strict_types=1);

namespace PaymentBundle\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use PaymentBundle\Services\Payments\DoumenIntlService;

/**
 * 批量触发斗门国际 GET /authorize 并将 token 写入 Redis 缓存。
 */
final class DoumenIntlScheduledTokenRefreshRunner
{
    private const REDIS_KEY_PREFIX = 'doumenIntlPaymentSetting:';

    private DoumenIntlService $doumenIntlService;

    public function __construct(DoumenIntlService $doumenIntlService)
    {
        $this->doumenIntlService = $doumenIntlService;
    }

    /**
     * @return array{attempted:int, ok:int, failed:int}
     */
    public function run(?int $companyId = null): array
    {
        $configs = $companyId !== null
            ? $this->loadConfigForCompany($companyId)
            : $this->scanEligibleConfigs();

        $attempted = count($configs);
        $ok = 0;
        $baseUri = (string) config('doumen_intl.base_url');
        $http = $this->resolveHttpClient($baseUri);
        $tokenStore = $this->createTokenStore();

        foreach ($configs as $config) {
            $refreshService = new DoumenIntlTokenRefreshService(
                (string) $config['X-AccessCode'],
                (string) $config['X-SecretKey'],
                $baseUri,
                $http,
                $tokenStore
            );
            if ($refreshService->refreshToken()) {
                ++$ok;
            }
        }

        return [
            'attempted' => $attempted,
            'ok' => $ok,
            'failed' => $attempted - $ok,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadConfigForCompany(int $companyId): array
    {
        if (! $this->doumenIntlService->isConfigured($companyId)) {
            return [];
        }

        $config = $this->getRawPaymentSetting($companyId);

        return $this->isEligibleConfig($config) ? [$config] : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scanEligibleConfigs(): array
    {
        $redis = app('redis');
        $keys = $redis->keys(self::REDIS_KEY_PREFIX.'*');
        if (! is_array($keys)) {
            return [];
        }

        $configs = [];
        foreach ($keys as $key) {
            $raw = $redis->get($key);
            if ($raw === false || $raw === null || $raw === '') {
                continue;
            }
            $decoded = json_decode((string) $raw, true);
            if (! is_array($decoded) || ! $this->isEligibleConfig($decoded)) {
                continue;
            }
            $configs[] = $decoded;
        }

        return $configs;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isEligibleConfig(array $data): bool
    {
        if (empty($data) || empty($data['is_open'])) {
            return false;
        }

        return ! empty($data['X-AccessCode'])
            && ! empty($data['X-SecretKey'])
            && ! empty($data['appId'])
            && ! empty($data['return_url']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getRawPaymentSetting(int $companyId): array
    {
        $redis = app('redis');
        $raw = $redis->get(self::REDIS_KEY_PREFIX.sha1((string) $companyId));
        if ($raw === false || $raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveHttpClient(string $baseUri): ClientInterface
    {
        if (app()->bound('doumen_intl.http_client')) {
            return app('doumen_intl.http_client');
        }

        return new Client(['base_uri' => $baseUri]);
    }

    private function createTokenStore(): object
    {
        $redis = app('redis');

        return new class($redis) {
            public function __construct(private $redis)
            {
            }

            public function get(string $key): ?array
            {
                $raw = $this->redis->get($key);
                if ($raw === false || $raw === null || $raw === '') {
                    return null;
                }
                $decoded = json_decode((string) $raw, true);
                if (! is_array($decoded) || ! isset($decoded['token'], $decoded['expires_at'])) {
                    return null;
                }
                if ((int) $decoded['expires_at'] <= time()) {
                    return null;
                }

                return [
                    'token' => (string) $decoded['token'],
                    'expires_at' => (int) $decoded['expires_at'],
                ];
            }

            public function set(string $key, string $token, int $ttlSeconds): void
            {
                $this->redis->set($key, json_encode([
                    'token' => $token,
                    'expires_at' => time() + $ttlSeconds,
                ]));
            }
        };
    }
}
