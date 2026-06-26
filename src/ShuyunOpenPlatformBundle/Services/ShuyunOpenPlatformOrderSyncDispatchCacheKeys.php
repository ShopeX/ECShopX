<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 数云订单/退款同步 Job 入队去重用的 Cache 键（与计划「重复事件」策略一致：短时间窗内同键只首次 dispatch）。
 */
final class ShuyunOpenPlatformOrderSyncDispatchCacheKeys
{
    /** 支付成功 → trade.sync Job，默认 TTL（秒） */
    public const TRADE_SYNC_DEDUPE_TTL_SEC = 600;
    /** 生命周期节点（发货/收货/取消/退款完成）→ trade.sync Job，按 trigger 分键，避免不同节点互相吞掉 */
    public const TRADE_SYNC_DEDUPE_TTL_SEC_PER_TRIGGER = 30;

    /**
     * 退款申请/完成 → refund.sync Job，按 lane 分键，避免申请与完成互相吞掉；TTL 仅抑制短时间重复事件。
     */
    public const REFUND_SYNC_DEDUPE_TTL_SEC_PER_LANE = 30;

    public const LOG_DISPATCH_DEDUPED = 'shuyun_open_platform_dispatch_deduped';

    public static function tradeSyncDedupeKey(int $companyId, string $orderId): string
    {
        return 'shuyun_open_platform:dispatch_dedupe:trade_sync:'.$companyId.':'.sha1($orderId);
    }

    public static function tradeSyncDedupeKeyByTrigger(int $companyId, string $orderId, string $trigger): string
    {
        return 'shuyun_open_platform:dispatch_dedupe:trade_sync:'.$companyId.':'.sha1($orderId).':'.$trigger;
    }

    /**
     * @param  'apply'|'finish'  $lane
     */
    public static function refundSyncDedupeKey(int $companyId, string $refundBn, string $lane): string
    {
        return 'shuyun_open_platform:dispatch_dedupe:refund_sync:'.$companyId.':'.sha1($refundBn).':'.$lane;
    }
}
