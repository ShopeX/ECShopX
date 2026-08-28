<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Auth;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * 基于 Laravel Cache 的 nonce 去重（生产入站回调验签使用）。
 */
final class CacheShuyunCallbackNonceStore implements ShuyunCallbackNonceStoreInterface
{
    private const KEY_PREFIX = 'shuyun_open_platform:callback_nonce:';

    public function __construct(
        private CacheRepository $cache,
    ) {
    }

    public function consume(string $nonce, int $ttlSeconds): bool
    {
        if ($nonce === '' || $ttlSeconds < 1) {
            return false;
        }

        $key = self::KEY_PREFIX.hash('sha256', $nonce);

        return $this->cache->add($key, 1, $ttlSeconds);
    }
}
