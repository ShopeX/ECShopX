<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Listeners;

use GoodsBundle\Entities\Items;
use GoodsBundle\Events\ItemStoreUpdateEvent;

/**
 * 店铺端库存变更后异步同步商品（含 SKU 库存）至数云。
 *
 * @see .tasks/plans/shuyun-open-platform-category-goods-sync.md A-PROD-02
 */
final class ItemStoreShuyunOpenPlatformProductSyncListener
{
    public function handle(ItemStoreUpdateEvent $event): void
    {
        $distributorId = (int) $event->distributor_id;
        if ($distributorId < 1) {
            return;
        }

        $itemId = (int) $event->item_id;
        if ($itemId < 1) {
            return;
        }

        $itemsRepo = app('registry')->getManager('default')->getRepository(Items::class);
        $item = $itemsRepo->getInfo(['item_id' => $itemId]);
        if ($item === [] || $item === null) {
            return;
        }

        $companyId = (int) ($item['company_id'] ?? 0);
        $defaultItemId = (int) ($item['default_item_id'] ?? 0);
        if ($defaultItemId < 1) {
            $defaultItemId = $itemId;
        }

        ItemsProductSyncToShuyunOpenPlatformDispatch::dispatchIfAuthAllows($companyId, $distributorId, $defaultItemId);
    }
}
