<?php

declare(strict_types=1);

namespace OpenapiBundle\Auth;

/**
 * OpenAPI 签名请求 nonce 去重：首次 consume 成功，重放返回 false。
 */
interface OpenapiNonceStoreInterface
{
    /**
     * @return bool true 表示 nonce 首次使用并已登记；false 表示重放或登记失败
     */
    public function consumeNonce(string $nonce, int $ttlSeconds): bool;
}
