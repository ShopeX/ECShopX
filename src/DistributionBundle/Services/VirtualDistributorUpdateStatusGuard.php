<?php

declare(strict_types=1);

/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 */

namespace DistributionBundle\Services;

use Dingo\Api\Exception\StoreResourceFailedException;

/**
 * 虚拟店（distributor_self = 1）禁止将 is_valid 改为非启用语义；与计划 V-STA-01 / D6 对齐。
 */
final class VirtualDistributorUpdateStatusGuard
{
    /**
     * @param  mixed  $requestIsValidValue  Request 上原始 is_valid（须与 $request->has('is_valid') 成对使用）
     *
     * @throws StoreResourceFailedException
     */
    public static function forbidNonEnabledStatusIntentForVirtualShop(
        bool $isVirtualShop,
        bool $hasIsValidIntent,
        $requestIsValidValue,
        bool $hasReviewResultIntent,
        string $reviewResult
    ): void {
        if (!$isVirtualShop) {
            return;
        }
        if (!$hasIsValidIntent && !$hasReviewResultIntent) {
            return;
        }
        if ($hasReviewResultIntent && $reviewResult !== 'true') {
            throw new StoreResourceFailedException(trans('DistributionBundle/Controllers/Distributor.virtual_shop_status_not_allowed'));
        }
        if ($hasIsValidIntent && !self::isEnabledSemanticIsValidValue($requestIsValidValue)) {
            throw new StoreResourceFailedException(trans('DistributionBundle/Controllers/Distributor.virtual_shop_status_not_allowed'));
        }
    }

    /**
     * 启用语义：与 2.4 节「is_valid 恒为 true/1」及 ShopSyncLifecycleResolver ENABLED 判定一致。
     *
     * @param  mixed  $value
     */
    public static function isEnabledSemanticIsValidValue($value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }
        if ($value === false || $value === 0) {
            return false;
        }

        $normalized = strtolower(trim((string) $value));

        return $normalized === 'true' || $normalized === '1';
    }
}
