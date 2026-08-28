<?php

declare(strict_types=1);

namespace OpenapiBundle\Auth;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * 基于 Laravel Cache 的 OpenAPI nonce 去重。
 */
final class CacheOpenapiNonceStore implements OpenapiNonceStoreInterface
{
    private const KEY_PREFIX = 'openapi:request_nonce:';

    public function __construct(
        private CacheRepository $cache,
    ) {
    }

    public function consumeNonce(string $nonce, int $ttlSeconds): bool
    {
        if ($nonce === '' || $ttlSeconds < 1) {
            return false;
        }

        $key = self::KEY_PREFIX.hash('sha256', $nonce);

        return $this->cache->add($key, 1, $ttlSeconds);
    }
}
