<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use DistributionBundle\Repositories\DistributorRepository;
use Illuminate\Support\Facades\Log;
use OrdersBundle\Repositories\NormalOrdersRepository;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendItem;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository;

/**
 * 订单支付成功 → 数云 {@see ShuyunOfflineBenefitReportService::pushResultV2} `USED`；
 * 订单取消且曾推过 USED → 再推 `NOT_USED`。
 *
 * 明细与订单关联依赖 {@see ShuyunOfflineBenefitSendItem::local_order_id}（由用券/履约链路写入；未写入则无推送，符合「非数云券不推」）。
 * `shopId` 从订单 {@see orders_normal_orders.distributor_id} 经 {@see ShuyunOpenPlatformGatewayShopIdResolver} 解析；失败则整单 skip。
 *
 * @see .tasks/plans/shuyun-offline-benefit-coupon.md §4.3、§7 T7
 * @see .tasks/plans/shuyun-shop-id-platform-migration.md
 */
class ShuyunOfflineBenefitConsumePushService
{
    public function __construct(
        private CompanyShuyunOpenPlatformConfigRepository $configRepository,
        private ShuyunOpenPlatformShopSyncService $shopSyncEligibility,
        private ShuyunOfflineBenefitReportService $reportService,
        private ShuyunOfflineBenefitSendItemRepository $itemRepository,
        private NormalOrdersRepository $normalOrdersRepository,
        private DistributorRepository $distributorRepository,
        private ShuyunOpenPlatformGatewayShopIdResolver $gatewayShopIdResolver,
    ) {
    }

    public function handlePaySuccess(int $companyId, int $orderId): void
    {
        if ($this->shouldSkipShuyunOfflineBenefitByOrder($companyId, $orderId)) {
            return;
        }

        if (!$this->isTenantEligible($companyId)) {
            return;
        }

        $shopId = $this->resolveShopIdForOrder($companyId, $orderId);
        if ($shopId === null) {
            return;
        }

        $platform = $this->gatewayPlatformHeader();
        $when = time();

        $items = $this->itemRepository->findSendItemsForOrderPayConsume($companyId, $orderId);
        foreach ($items as $item) {
            if (!$item instanceof ShuyunOfflineBenefitSendItem) {
                continue;
            }
            if ($item->getLastConsumeStatus() === 'USED') {
                continue;
            }

            $batch = $item->getBatch();
            if (!$batch instanceof ShuyunOfflineBenefitSendBatch) {
                continue;
            }

            $row = $this->buildResultRow($batch, $item, (string) $orderId, 'USED', $when, $shopId);
            if ($this->reportService->pushResultV2($companyId, $platform, [$row])) {
                $item->setLastConsumeStatus('USED');
                $item->setLastConsumePushAt($when);
                $this->itemRepository->save($item);
            }
        }
    }

    public function handleOrderCancel(int $companyId, int $orderId): void
    {
        if ($this->shouldSkipShuyunOfflineBenefitByOrder($companyId, $orderId)) {
            return;
        }

        if (!$this->isTenantEligible($companyId)) {
            return;
        }

        $shopId = $this->resolveShopIdForOrder($companyId, $orderId);
        if ($shopId === null) {
            return;
        }

        $platform = $this->gatewayPlatformHeader();
        $when = time();

        $items = $this->itemRepository->findSendItemsForOrderCancelNotUsed($companyId, $orderId);
        foreach ($items as $item) {
            if (!$item instanceof ShuyunOfflineBenefitSendItem) {
                continue;
            }
            if ($item->getLastConsumeStatus() === 'NOT_USED') {
                continue;
            }

            $batch = $item->getBatch();
            if (!$batch instanceof ShuyunOfflineBenefitSendBatch) {
                continue;
            }

            $row = $this->buildResultRow($batch, $item, (string) $orderId, 'NOT_USED', $when, $shopId);
            if ($this->reportService->pushResultV2($companyId, $platform, [$row])) {
                $item->setLastConsumeStatus('NOT_USED');
                $item->setLastConsumePushAt($when);
                $this->itemRepository->save($item);
            }
        }
    }

    private function isTenantEligible(int $companyId): bool
    {
        $config = $this->configRepository->findOneByCompanyId($companyId);

        return $config instanceof CompanyShuyunOpenPlatformConfig
            && $this->shopSyncEligibility->isEligible($config);
    }

    /**
     * 与数云 trade/refund 同步一致：无有效会员或订单实付 0 元（分）不推线下权益核销/返还；查不到主单时不拦截（与旧行为及无库单测一致）。
     */
    private function shouldSkipShuyunOfflineBenefitByOrder(int $companyId, int $orderId): bool
    {
        if ($orderId < 1 || $companyId < 1) {
            return true;
        }

        $row = $this->normalOrdersRepository->getInfo([
            'company_id' => $companyId,
            'order_id' => (string) $orderId,
        ]);
        if ($row === []) {
            return false;
        }

        if ((int) ($row['user_id'] ?? 0) === 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun offline_benefit_consume push skipped: user_id is zero.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return true;
        }
        if ((int) ($row['total_fee'] ?? 0) <= 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun offline_benefit_consume push skipped: total_fee is zero (fen).', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return true;
        }

        return false;
    }

    private function resolveShopIdForOrder(int $companyId, int $orderId): ?string
    {
        $order = $this->normalOrdersRepository->getInfo([
            'company_id' => $companyId,
            'order_id' => (string) $orderId,
        ]);
        if ($order === []) {
            Log::channel('shuyun_open_platform')->info('Shuyun offline_benefit_consume push skipped: order not found for shopId.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ]);

            return null;
        }

        $distributorId = (int) ($order['distributor_id'] ?? 0);
        if ($distributorId <= 0) {
            Log::channel('shuyun_open_platform')->info('Shuyun offline_benefit_consume push skipped: invalid distributor_id.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'distributor_id' => $distributorId,
            ]);

            return null;
        }

        $distributorRow = $this->distributorRepository->getInfo([
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
        ]);
        if (!is_array($distributorRow) || $distributorRow === []) {
            Log::channel('shuyun_open_platform')->info('Shuyun offline_benefit_consume push skipped: distributor not found.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'distributor_id' => $distributorId,
            ]);

            return null;
        }

        try {
            return $this->gatewayShopIdResolver->resolve($distributorRow);
        } catch (\InvalidArgumentException $e) {
            Log::channel('shuyun_open_platform')->warning('Shuyun offline_benefit_consume push skipped: cannot resolve shopId.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'distributor_id' => $distributorId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function gatewayPlatformHeader(): string
    {
        $platform = strtolower(trim((string) config('shuyun_open_platform.offline_benefit_gateway_platform', 'offline')));

        return $platform !== '' ? $platform : 'offline';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResultRow(
        ShuyunOfflineBenefitSendBatch $batch,
        ShuyunOfflineBenefitSendItem $item,
        string $orderId,
        string $status,
        int $whenUnix,
        string $shopId
    ): array {
        $row = [
            'benefitId' => $batch->getBenefitId(),
            'requestId' => $batch->getRequestId(),
            'benefitCode' => $item->getBenefitCode() ?? '',
            'platCode' => 'OFFLINE',
            'shopId' => $shopId,
            'customerId' => $item->getCustomerId(),
            'status' => $status,
            'orderId' => $orderId,
            'useTime' => date('Y-m-d H:i:s', $whenUnix),
            'remark' => '',
        ];

        return $row;
    }
}
