<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use Illuminate\Contracts\Cache\Repository;

/**
 * 数云开放相关异步任务：同一 merge key 在时间窗内多次触发只入队一次（后续类目/商品 Job 可复用同一套键规则）。
 *
 * @see .tasks/plans/shuyun-open-platform-category-goods-sync.md A-MERGE-01、A-MERGE-02
 */
final class ShuyunOpenPlatformMergedJobDispatchService
{
    /** 店铺同步合并键前缀 {@see self::shopSyncMergeKey()} */
    public const MERGE_PREFIX_SHOP_SYNC = 'shop_sync';

    /** 预留：类目同步 company + category 维度 */
    public const MERGE_PREFIX_CATEGORY_SYNC = 'category_sync';

    /** 商品同步：company + 店铺 + SPU（default_item_id） */
    public const MERGE_PREFIX_PRODUCT_SYNC = 'product_sync';

    private Repository $cache;

    private int $ttlSeconds;

    public function __construct(Repository $cache, int $ttlSeconds)
    {
        $this->cache = $cache;
        $this->ttlSeconds = $ttlSeconds;
    }

    public static function shopSyncMergeKey(int $companyId, int $distributorId): string
    {
        return self::MERGE_PREFIX_SHOP_SYNC.':'.$companyId.':'.$distributorId;
    }

    public static function categorySyncMergeKey(int $companyId, int $categoryId): string
    {
        return self::MERGE_PREFIX_CATEGORY_SYNC.':'.$companyId.':'.$categoryId;
    }

    public static function productSyncMergeKey(int $companyId, int $distributorId, int $defaultItemId): string
    {
        return self::MERGE_PREFIX_PRODUCT_SYNC.':'.$companyId.':'.$distributorId.':'.$defaultItemId;
    }

    /**
     * @param  \Closure():void  $dispatch
     * @return bool true 已执行入队；false 因合并跳过本次入队
     */
    public function dispatchUnlessMerged(string $mergeKey, \Closure $dispatch): bool
    {
        if ($this->ttlSeconds < 1) {
            $dispatch();

            return true;
        }
        $cacheKey = 'shuyun_open_platform:merge:'.hash('sha256', $mergeKey);
        if (!$this->cache->add($cacheKey, 1, $this->ttlSeconds)) {
            return false;
        }
        $dispatch();

        return true;
    }
}
