<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 数云开放网关 product_id：优先 items.goods_id，回退 default_item_id，再回退 SKU item_id。
 */
final class ShuyunOpenPlatformItemProductIdResolver
{
    public static function resolve(int $goodsId, int $defaultItemId, int $fallbackItemId = 0): string
    {
        if ($goodsId > 0) {
            return (string) $goodsId;
        }
        if ($defaultItemId > 0) {
            return (string) $defaultItemId;
        }
        if ($fallbackItemId > 0) {
            return (string) $fallbackItemId;
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $itemRow
     */
    public static function resolveFromItemRow(array $itemRow, int $defaultItemId = 0): string
    {
        $spuDefaultItemId = $defaultItemId > 0
            ? $defaultItemId
            : (int) ($itemRow['default_item_id'] ?? 0);
        $lineItemId = (int) ($itemRow['item_id'] ?? 0);

        return self::resolve(
            (int) ($itemRow['goods_id'] ?? 0),
            $spuDefaultItemId,
            $lineItemId,
        );
    }
}
