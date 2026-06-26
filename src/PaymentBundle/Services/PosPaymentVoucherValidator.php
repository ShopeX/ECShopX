<?php

/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 */

namespace PaymentBundle\Services;

use Dingo\Api\Exception\ResourceException;

/**
 * 店务 POS 支付可选凭证 URL：仅校验格式与长度，不做服务端抓取。
 */
final class PosPaymentVoucherValidator
{
    public const MAX_LENGTH = 2048;

    /**
     * @throws ResourceException
     */
    public static function validateNonEmpty(string $url): void
    {
        if (strlen($url) > self::MAX_LENGTH) {
            throw new ResourceException('凭证地址过长');
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ResourceException('凭证地址格式不正确');
        }
        $parts = parse_url($url);
        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new ResourceException('凭证地址仅支持 http 或 https');
        }
    }
}
