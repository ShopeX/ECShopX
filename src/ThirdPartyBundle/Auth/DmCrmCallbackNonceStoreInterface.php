<?php

declare(strict_types=1);

namespace ThirdPartyBundle\Auth;

/**
 * DM CRM 入站回调 nonce 去重：首次 consume 成功，重放返回 false。
 */
interface DmCrmCallbackNonceStoreInterface
{
    /**
     * @return bool true 表示 nonce 首次使用并已登记；false 表示重放或登记失败
     */
    public function consumeNonce(string $nonce, int $ttlSeconds): bool;
}
