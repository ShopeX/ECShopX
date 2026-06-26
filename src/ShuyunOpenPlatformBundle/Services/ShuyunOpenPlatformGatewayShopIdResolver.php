<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 数云开放网关各接口中「店铺标识」字段（如 body `shop_id`、query `shopId`）的取值统一为
 * `distribution_distributor.distributor_id`（字符串化）+ {@see ShuyunOpenPlatformOfflinePlatGatewaySuffixApplier}（默认 `-off`），不使用 `shop_code`。
 */
final class ShuyunOpenPlatformGatewayShopIdResolver
{
    /**
     * @param  array<string, mixed>  $distributorRow  distribution_distributor 行
     *
     * @return non-empty-string
     */
    public function resolve(array $distributorRow): string
    {
        $id = (int) ($distributorRow['distributor_id'] ?? 0);
        if ($id <= 0) {
            throw new \InvalidArgumentException('distributor_id is missing or invalid for Shuyun gateway shop identifier.');
        }

        return ShuyunOpenPlatformOfflinePlatGatewaySuffixApplier::applyExternalIdSuffix((string) $id);
    }
}
