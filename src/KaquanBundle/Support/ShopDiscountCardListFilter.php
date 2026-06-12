<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace KaquanBundle\Support;

/**
 * 店铺端优惠券列表可见性过滤（理解 B：本店创建 或 适用本店）。
 */
final class ShopDiscountCardListFilter
{
    /**
     * @param array<string, mixed> $filter
     */
    public static function applyToFilter(array &$filter, int $distributorId): bool
    {
        if ($distributorId <= 0) {
            return false;
        }

        $filter['or'] = self::orConditionsForShop($distributorId);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public static function orConditionsForShop(int $distributorId): array
    {
        return [
            'source_id' => $distributorId,
            'use_all_shops' => 1,
            // DiscountCardsRepository::__orFilter 的 like 不会自动包裹 %
            'distributor_id|like' => '%,' . $distributorId . ',%',
            'distributor_id' => ',',
        ];
    }

    /**
     * @param array<string, mixed> $coupon
     */
    public static function matchesCoupon(array $coupon, int $distributorId): bool
    {
        if ($distributorId <= 0) {
            return true;
        }

        if ((int) ($coupon['source_id'] ?? 0) === $distributorId) {
            return true;
        }

        if (!empty($coupon['use_all_shops']) && (int) $coupon['use_all_shops'] === 1) {
            return true;
        }

        $distributorIdField = (string) ($coupon['distributor_id'] ?? '');
        if ($distributorIdField === ',') {
            return true;
        }

        return str_contains($distributorIdField, ',' . $distributorId . ',');
    }
}
