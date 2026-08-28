<?php

declare(strict_types=1);

namespace ThirdPartyBundle\Auth;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * 基于 Laravel Cache 的 DM CRM 回调 nonce 去重。
 */
final class CacheDmCrmCallbackNonceStore implements DmCrmCallbackNonceStoreInterface
{
    private const KEY_PREFIX = 'dm_crm:callback_nonce:';

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
