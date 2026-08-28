<?php

declare(strict_types=1);

namespace GoodsBundle\Support;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;

/**
 * Goods item tenant-scope gate: bind auth company_id on item reads/writes.
 */
class GoodsTenantScopeGuard
{
    /**
     * @param array<string, mixed> $row
     */
    public static function assertCompanyScope(array $row, int $companyId): void
    {
        if ($companyId <= 0 || empty($row) || (int) ($row['company_id'] ?? 0) !== $companyId) {
            throw new ResourceException('无权访问该商品');
        }
    }

    /**
     * @param int[]|string[] $itemIds
     */
    public static function assertItemIdsBelongToCompany(int $companyId, array $itemIds): void
    {
        if ($companyId <= 0 || $itemIds === []) {
            throw new ResourceException('无权访问该商品');
        }

        $itemsService = new ItemsService();
        foreach ($itemIds as $itemId) {
            $itemId = (int) $itemId;
            if ($itemId <= 0) {
                throw new ResourceException('无权访问该商品');
            }

            $itemInfo = $itemsService->getInfo(['company_id' => $companyId, 'item_id' => $itemId]);
            if (!$itemInfo) {
                throw new ResourceException('无权访问该商品');
            }
        }
    }
}
