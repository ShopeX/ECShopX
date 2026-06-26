<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Listeners;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Events\NormalOrderCancelEvent;
use OrdersBundle\Events\NormalOrderConfirmReceiptEvent;
use OrdersBundle\Events\NormalOrderDeliveryEvent;
use OrdersBundle\Events\NormalOrderPaySuccessEvent;
use OrdersBundle\Repositories\NormalOrdersRepository;
use ShuyunOpenPlatformBundle\Jobs\SyncNormalOrderTradeToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderSyncDispatchCacheKeys;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ThirdPartyBundle\Events\TradeRefundFinishEvent;

/**
 * 订单关键生命周期事件（支付/发货/收货/取消/退款完成）→ 数云订单正向同步 Job。
 */
final class DispatchNormalOrderTradeSyncToShuyunOpenPlatformListener
{
    public function handle(NormalOrderPaySuccessEvent $event): void
    {
        $this->dispatchFromEntities($event->entities, 'pay_success', false);
    }

    public function handleDelivery(NormalOrderDeliveryEvent $event): void
    {
        $this->dispatchFromEntities($event->entities, 'delivery', false);
    }

    public function handleConfirmReceipt(NormalOrderConfirmReceiptEvent $event): void
    {
        $this->dispatchFromEntities($event->entities, 'confirm_receipt', false);
    }

    public function handleOrderCancel(NormalOrderCancelEvent $event): void
    {
        // 取消场景要求仅处理已支付订单，避免未支付取消误触发 trade.sync。
        $this->dispatchFromEntities($event->entities, 'order_cancel', true);
    }

    public function handleTradeRefundFinish(TradeRefundFinishEvent $event): void
    {
        // 退款完成后按订单最新状态重推一次，覆盖 TRADE_CLOSED_ALL_REFUND 等终态。
        $this->dispatchFromEntities($event->entities, 'refund_finish', true);
    }

    /**
     * @param array<string,mixed>|mixed $entities
     */
    private function dispatchFromEntities($entities, string $trigger, bool $requirePayed): void
    {
        if (! is_array($entities)) {
            return;
        }

        $data = $entities;
        $companyId = (int) ($data['company_id'] ?? 0);
        $orderId = trim((string) ($data['order_id'] ?? ''));
        // 默认 stack→lumen 日志；与 shuyun_open_platform 通道独立。检索：ShuyunOpenPlatform_trade_sync_listener
        Log::info('ShuyunOpenPlatform_trade_sync_listener', [
            'step' => 'enter_'.$trigger,
            'company_id' => $companyId,
            'order_id' => $orderId,
        ]);
        if ($companyId < 1 || $orderId === '') {
            Log::info('ShuyunOpenPlatform_trade_sync_listener', [
                'step' => 'skip_empty_company_or_order',
            ]);

            return;
        }

        // 无有效会员、0 元实付单不同步数云；未带 user_id/total_fee 时交由 Job 内按库表再判
        if (array_key_exists('user_id', $data) && (int) $data['user_id'] === 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun trade_sync dispatch skipped: user_id is zero.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return;
        }
        if (array_key_exists('total_fee', $data) && (int) $data['total_fee'] <= 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun trade_sync dispatch skipped: total_fee is zero (fen).', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return;
        }

        if ($requirePayed && ! $this->isPayedOrder($companyId, $orderId)) {
            Log::channel('shuyun_open_platform')->info('Shuyun trade_sync dispatch skipped: order is not PAYED.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'trigger' => $trigger,
            ]);

            return;
        }

        $repo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $row = $repo->findOneByCompanyId($companyId);
        $shopSync = app(ShuyunOpenPlatformShopSyncService::class);
        // 与 {@see SyncNormalOrderTradeToShuyunOpenPlatformJob} 使用同一套 isEligible，避免入队后 Job 静默跳过
        if (! $shopSync->isEligible($row)) {
            Log::channel('shuyun_open_platform')->info('Shuyun trade_sync dispatch skipped: open platform not eligible.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'has_config_row' => $row !== null,
                'is_enabled' => $row !== null ? (int) $row->getIsEnabled() : null,
            ]);

            return;
        }

        $dedupeKey = $trigger === 'pay_success'
            ? ShuyunOpenPlatformOrderSyncDispatchCacheKeys::tradeSyncDedupeKey($companyId, $orderId)
            : ShuyunOpenPlatformOrderSyncDispatchCacheKeys::tradeSyncDedupeKeyByTrigger($companyId, $orderId, $trigger);
        $dedupeTtl = $trigger === 'pay_success'
            ? ShuyunOpenPlatformOrderSyncDispatchCacheKeys::TRADE_SYNC_DEDUPE_TTL_SEC
            : ShuyunOpenPlatformOrderSyncDispatchCacheKeys::TRADE_SYNC_DEDUPE_TTL_SEC_PER_TRIGGER;
        if (! Cache::add(
            $dedupeKey,
            1,
            $dedupeTtl
        )) {
            Log::channel('shuyun_open_platform')->info(ShuyunOpenPlatformOrderSyncDispatchCacheKeys::LOG_DISPATCH_DEDUPED, [
                'kind' => 'trade_sync',
                'company_id' => $companyId,
                'order_id' => $orderId,
                'trigger' => $trigger,
            ]);

            return;
        }

        $job = (new SyncNormalOrderTradeToShuyunOpenPlatformJob($companyId, $orderId))->onQueue('slow');
        $conn = app('registry')->getConnection('default');
        if ($trigger === 'pay_success' && $conn->isTransactionActive()) {
            $job->delay(3);
            Log::channel('shuyun_open_platform')->info('Shuyun trade_sync_job_dispatched_delayed_for_open_transaction', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'delay_sec' => 3,
            ]);
        }
        app(Dispatcher::class)->dispatch($job);

        // 检索关键字：trade_sync_job_dispatched（与队列 worker 里是否出现 Job 类名无关，用于确认已派发）
        Log::channel('shuyun_open_platform')->info('Shuyun trade_sync_job_dispatched', [
            'queue' => 'slow',
            'company_id' => $companyId,
            'order_id' => $orderId,
            'trigger' => $trigger,
            'job' => SyncNormalOrderTradeToShuyunOpenPlatformJob::class,
        ]);
    }

    private function isPayedOrder(int $companyId, string $orderId): bool
    {
        if ($companyId < 1 || $orderId === '') {
            return false;
        }

        /** @var NormalOrdersRepository $normalOrdersRepository */
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderRow = $normalOrdersRepository->getInfo([
            'company_id' => $companyId,
            'order_id' => $orderId,
        ]);
        if ($orderRow === []) {
            return false;
        }

        return (string) ($orderRow['pay_status'] ?? '') === 'PAYED';
    }
}
