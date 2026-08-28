<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Auth;

/**
 * 进程内 nonce 存储，供单元测试与单进程联调使用。
 */
final class InMemoryShuyunCallbackNonceStore implements ShuyunCallbackNonceStoreInterface
{
    /** @var array<string, true> */
    private array $seen = [];

    public function consume(string $nonce, int $ttlSeconds): bool
    {
        unset($ttlSeconds);

        if ($nonce === '') {
            return false;
        }

        if (isset($this->seen[$nonce])) {
            return false;
        }

        $this->seen[$nonce] = true;

        return true;
    }
}
