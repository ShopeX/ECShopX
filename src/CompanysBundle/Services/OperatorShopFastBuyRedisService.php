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

namespace CompanysBundle\Services;

/**
 * 店务立即购买分桶：与小程序 fastbuy Redis 思路一致，key 维度独立（operator + company + distributor）。
 */
final class OperatorShopFastBuyRedisService
{
    private const KEY_PREFIX = 'shop_fastbuy:';

    private const TTL_SECONDS = 600;

    public function buildKey(int $companyId, int $operatorId, int $distributorId): string
    {
        return self::KEY_PREFIX . sha1($companyId . ':' . $operatorId . ':' . $distributorId);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function set(int $companyId, int $operatorId, int $distributorId, array $payload): void
    {
        $key = $this->buildKey($companyId, $operatorId, $distributorId);
        $payload['cart_id'] = 0;
        app('redis')->setex($key, self::TTL_SECONDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int $companyId, int $operatorId, int $distributorId): array
    {
        $key = $this->buildKey($companyId, $operatorId, $distributorId);
        $raw = app('redis')->get($key);
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function clear(int $companyId, int $operatorId, int $distributorId): void
    {
        $this->set($companyId, $operatorId, $distributorId, []);
    }
}
