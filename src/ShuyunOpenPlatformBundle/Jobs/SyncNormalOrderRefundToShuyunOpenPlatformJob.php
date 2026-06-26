<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Jobs;

use AftersalesBundle\Entities\AftersalesRefund;
use AftersalesBundle\Repositories\AftersalesRefundRepository;
use EspierBundle\Jobs\Job;
use Illuminate\Support\Facades\Log;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Repositories\NormalOrdersRepository;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformNormalOrderRefundPayloadAssembler;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderPlatformResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderTradeSourceResolver;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformRefundSyncService;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformShopSyncService;

/**
 * 退款单为 SUCCESS 时异步 {@see shuyun.base.refund.sync}（与 Listener 一致；Job 内再次校验防竞态）。
 */
class SyncNormalOrderRefundToShuyunOpenPlatformJob extends Job
{
    /** @var int */
    public $companyId;

    /** @var string */
    public $refundBn;

    public function __construct(int $companyId, string $refundBn)
    {
        $this->companyId = $companyId;
        $this->refundBn = $refundBn;
    }

    public function handle(): bool
    {
        if ($this->companyId < 1 || trim($this->refundBn) === '') {
            return true;
        }

        $configRepo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $openCfg = $configRepo->findOneByCompanyId($this->companyId);
        $shopSvc = app(ShuyunOpenPlatformShopSyncService::class);
        if ($openCfg === null || ! $shopSvc->isEligible($openCfg)) {
            return true;
        }

        $refundBn = trim($this->refundBn);
        $assembler = app(ShuyunOpenPlatformNormalOrderRefundPayloadAssembler::class);

        /** @var NormalOrdersRepository $normalOrdersRepository */
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        /** @var AftersalesRefundRepository $refundRepo */
        $refundRepo = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
        $refundRow = $refundRepo->getInfo([
            'company_id' => $this->companyId,
            'refund_bn' => $refundBn,
        ]);
        if ($refundRow === []) {
            return true;
        }
        if ((string) ($refundRow['refund_status'] ?? '') !== 'SUCCESS') {
            Log::channel('shuyun_open_platform')->info('Shuyun refund.sync skipped: refund_status not SUCCESS.', [
                'company_id' => $this->companyId,
                'refund_bn' => $refundBn,
                'refund_status' => (string) ($refundRow['refund_status'] ?? ''),
            ]);

            return true;
        }
        $orderId = trim((string) ($refundRow['order_id'] ?? ''));
        if ($orderId === '') {
            return true;
        }
        $orderRow = $normalOrdersRepository->getInfo([
            'company_id' => $this->companyId,
            'order_id' => $orderId,
        ]);
        if ($orderRow === []) {
            return true;
        }

        $orderUserId = (int) ($orderRow['user_id'] ?? 0);
        $orderTotalFen = (int) ($orderRow['total_fee'] ?? 0);
        if ($orderUserId === 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun refund.sync job skipped: user_id is zero.', [
                'company_id' => $this->companyId,
                'refund_bn' => $refundBn,
                'order_id' => $orderId,
            ]);

            return true;
        }
        if ($orderTotalFen <= 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun refund.sync job skipped: total_fee is zero (fen).', [
                'company_id' => $this->companyId,
                'refund_bn' => $refundBn,
                'order_id' => $orderId,
            ]);

            return true;
        }

        $orderClass = (string) ($orderRow['order_class'] ?? '');
        $tradeSourceResolver = app(ShuyunOpenPlatformOrderTradeSourceResolver::class);
        $tradeSource = $tradeSourceResolver->resolveTradeSourceForOrder($this->companyId, $orderId, $orderClass);
        if ($tradeSource === null) {
            Log::channel('shuyun_open_platform')->info('Shuyun refund.sync job skipped: trade_source not resolved (see shuyun_open_platform_trade_source_unknown in app log).', [
                'company_id' => $this->companyId,
                'refund_bn' => $refundBn,
                'order_id' => $orderId,
                'order_class' => $orderClass,
            ]);

            return true;
        }

        $platform = app(ShuyunOpenPlatformOrderPlatformResolver::class)->resolvePlatformHeaderForOrderClass($orderClass);

        $payloads = $assembler->buildRefundPayloads($this->companyId, $refundBn);
        if ($payloads === []) {
            Log::channel('shuyun_open_platform')->info('Shuyun refund.sync job skipped: buildRefundPayloads returned empty.', [
                'company_id' => $this->companyId,
                'refund_bn' => $refundBn,
            ]);

            return true;
        }
        $sync = app(ShuyunOpenPlatformRefundSyncService::class);
        $ok = $sync->syncValidatedRefunds($this->companyId, $platform, $payloads);
        if (! $ok) {
            throw new \RuntimeException('Shuyun refund.sync failed for refund_bn '.$refundBn);
        }

        return true;
    }
}
