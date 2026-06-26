<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Jobs;

use EspierBundle\Jobs\Job;
use Illuminate\Support\Facades\Log;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Repositories\NormalOrdersRepository;
use OrdersBundle\Services\OrderPostPayIntegrationReadinessService;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformNormalOrderTradePayloadAssembler;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderPlatformResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderTradeSourceResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformTradeSyncService;

/**
 * 支付成功后异步 {@see shuyun.base.trade.sync}。
 */
class SyncNormalOrderTradeToShuyunOpenPlatformJob extends Job
{
    private const ORDER_ROW_MISSING_RETRY_MAX_ATTEMPTS = 3;
    private const ORDER_ROW_MISSING_RETRY_DELAY_SEC = 3;
    private const ORDER_NOT_READY_RETRY_MAX_ATTEMPTS = 5;
    private const ORDER_NOT_READY_RETRY_DELAY_SEC = 3;

    /** @var int */
    public $companyId;

    /** @var string */
    public $orderId;

    public function __construct(int $companyId, string $orderId)
    {
        $this->companyId = $companyId;
        $this->orderId = $orderId;
    }

    public function handle(): bool
    {
        if ($this->companyId < 1 || $this->orderId === '') {
            Log::channel('shuyun_open_platform')->info('Shuyun trade_sync job skipped: invalid args.', [
                'company_id' => $this->companyId,
                'order_id' => $this->orderId,
            ]);

            return true;
        }

        // 检索关键字：trade_sync_job_handle_started（worker 已从队列取出并开始执行本 Job）
        Log::channel('shuyun_open_platform')->info('Shuyun trade_sync_job_handle_started', [
            'company_id' => $this->companyId,
            'order_id' => $this->orderId,
        ]);

        $configRepo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $openCfg = $configRepo->findOneByCompanyId($this->companyId);
        $shopSvc = app(ShuyunOpenPlatformShopSyncService::class);
        if ($openCfg === null || ! $shopSvc->isEligible($openCfg)) {
            Log::channel('shuyun_open_platform')->info('Shuyun trade_sync job skipped: tenant not eligible.', [
                'company_id' => $this->companyId,
                'order_id' => $this->orderId,
                'has_config_row' => $openCfg !== null,
            ]);

            return true;
        }

        /** @var NormalOrdersRepository $normalOrdersRepository */
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderRow = $normalOrdersRepository->getInfo([
            'company_id' => $this->companyId,
            'order_id' => $this->orderId,
        ]);
        if ($orderRow === []) {
            $attempt = (int) $this->attempts();
            if ($attempt < self::ORDER_ROW_MISSING_RETRY_MAX_ATTEMPTS) {
                Log::channel('shuyun_open_platform')->info('Shuyun trade_sync job order row missing, will retry.', [
                    'company_id' => $this->companyId,
                    'order_id' => $this->orderId,
                    'attempt' => $attempt,
                    'max_attempts' => self::ORDER_ROW_MISSING_RETRY_MAX_ATTEMPTS,
                    'retry_delay_sec' => self::ORDER_ROW_MISSING_RETRY_DELAY_SEC,
                ]);
                $this->release(self::ORDER_ROW_MISSING_RETRY_DELAY_SEC);

                return true;
            }
            Log::channel('shuyun_open_platform')->warning('Shuyun trade_sync job skipped: order row missing.', [
                'company_id' => $this->companyId,
                'order_id' => $this->orderId,
                'attempt' => $attempt,
            ]);

            return true;
        }

        $readiness = app(OrderPostPayIntegrationReadinessService::class);
        if (! $readiness->isOrderRowReadyForPostPayIntegration($orderRow)) {
            $attempt = (int) $this->attempts();
            if ($readiness->orderHasSuccessfulTrade($this->companyId, $this->orderId)
                && $attempt < self::ORDER_NOT_READY_RETRY_MAX_ATTEMPTS) {
                Log::channel('shuyun_open_platform')->info('Shuyun trade_sync job order not ready after pay, will retry.', [
                    'company_id' => $this->companyId,
                    'order_id' => $this->orderId,
                    'order_status' => $orderRow['order_status'] ?? null,
                    'pay_status' => $orderRow['pay_status'] ?? null,
                    'attempt' => $attempt,
                    'max_attempts' => self::ORDER_NOT_READY_RETRY_MAX_ATTEMPTS,
                    'retry_delay_sec' => self::ORDER_NOT_READY_RETRY_DELAY_SEC,
                ]);
                $this->release(self::ORDER_NOT_READY_RETRY_DELAY_SEC);

                return true;
            }
            Log::channel('shuyun_open_platform')->warning('Shuyun trade_sync job skipped: order not ready for post-pay sync.', [
                'company_id' => $this->companyId,
                'order_id' => $this->orderId,
                'order_status' => $orderRow['order_status'] ?? null,
                'pay_status' => $orderRow['pay_status'] ?? null,
                'attempt' => $attempt,
            ]);

            return true;
        }

        $orderUserId = (int) ($orderRow['user_id'] ?? 0);
        $orderTotalFen = (int) ($orderRow['total_fee'] ?? 0);
        if ($orderUserId === 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun trade_sync job skipped: user_id is zero.', [
                'company_id' => $this->companyId,
                'order_id' => $this->orderId,
            ]);

            return true;
        }
        if ($orderTotalFen <= 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun trade_sync job skipped: total_fee is zero (fen).', [
                'company_id' => $this->companyId,
                'order_id' => $this->orderId,
            ]);

            return true;
        }

        $orderClass = (string) ($orderRow['order_class'] ?? '');
        $tradeSourceResolver = app(ShuyunOpenPlatformOrderTradeSourceResolver::class);
        $tradeSource = $tradeSourceResolver->resolveTradeSourceForOrder($this->companyId, $this->orderId, $orderClass);
        if ($tradeSource === null) {
            Log::channel('shuyun_open_platform')->info('Shuyun trade_sync job skipped: trade_source unresolved.', [
                'company_id' => $this->companyId,
                'order_id' => $this->orderId,
                'order_class' => $orderClass,
            ]);

            return true;
        }

        $platform = app(ShuyunOpenPlatformOrderPlatformResolver::class)->resolvePlatformHeaderForOrderClass($orderClass);

        $assembler = app(ShuyunOpenPlatformNormalOrderTradePayloadAssembler::class);
        $payload = $assembler->buildOneOrderPayload($this->companyId, $this->orderId, $tradeSource);
        if ($payload === null) {
            Log::channel('shuyun_open_platform')->warning('Shuyun trade_sync job skipped: payload build returned null.', [
                'company_id' => $this->companyId,
                'order_id' => $this->orderId,
                'trade_source' => $tradeSource,
            ]);

            return true;
        }
        $sync = app(ShuyunOpenPlatformTradeSyncService::class);
        $ok = $sync->syncValidatedTradeOrders($this->companyId, $platform, [$payload]);
        if (! $ok) {
            throw new \RuntimeException('Shuyun trade.sync failed for order '.$this->orderId);
        }

        return true;
    }
}
