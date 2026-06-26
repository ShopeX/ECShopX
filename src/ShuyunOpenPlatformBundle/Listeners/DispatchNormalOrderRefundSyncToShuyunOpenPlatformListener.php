<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Listeners;

use AftersalesBundle\Entities\AftersalesRefund;
use AftersalesBundle\Repositories\AftersalesRefundRepository;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Repositories\NormalOrdersRepository;
use ShuyunOpenPlatformBundle\Entities\CompanyShuyunOpenPlatformConfig;
use ShuyunOpenPlatformBundle\Jobs\SyncNormalOrderRefundToShuyunOpenPlatformJob;
use ShuyunOpenPlatformBundle\Repositories\CompanyShuyunOpenPlatformConfigRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOpenPlatformOrderSyncDispatchCacheKeys;

/**
 * 退款申请（{@see \SystemLinkBundle\Events\TradeRefundEvent} / {@see \ThirdPartyBundle\Events\TradeRefundEvent}）
 * 与退款完成（{@see \SystemLinkBundle\Events\TradeRefundFinishEvent} / {@see \ThirdPartyBundle\Events\TradeRefundFinishEvent}）→ 数云逆向同步 Job。
 * 仅当 {@see AftersalesRefund} 行为 SUCCESS（退款成功）时才入队；CHANGE（退款异常）等不推送数云 refund.sync。
 */
final class DispatchNormalOrderRefundSyncToShuyunOpenPlatformListener
{
    public function handleTradeRefund(object $event): void
    {
        $data = $event->entities ?? null;
        if (! is_array($data)) {
            return;
        }
        $this->dispatchForEntities($data, 'apply');
    }

    public function handleTradeRefundFinish(object $event): void
    {
        $data = $event->entities ?? null;
        if (! is_array($data)) {
            return;
        }
        $this->dispatchForEntities($data, 'finish');
    }

    /**
     * @param  array<string, mixed>  $entities
     * @param  'apply'|'finish'      $lane
     */
    private function dispatchForEntities(array $entities, string $lane): void
    {
        $refundBn = $this->resolveRefundBn($entities);
        if ($refundBn === null) {
            return;
        }
        $companyId = (int) ($entities['company_id'] ?? 0);
        if ($companyId < 1) {
            return;
        }

        $repo = app(CompanyShuyunOpenPlatformConfigRepository::class);
        $row = $repo->findOneByCompanyId($companyId);
        if (! $row instanceof CompanyShuyunOpenPlatformConfig || (int) $row->getIsEnabled() !== 1) {
            return;
        }
        if (trim((string) $row->getAuthValue()) === '') {
            return;
        }

        /** @var AftersalesRefundRepository $refundRepo */
        $refundRepo = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
        $refundRow = $refundRepo->getInfo([
            'company_id' => $companyId,
            'refund_bn' => $refundBn,
        ]);
        if ($refundRow === [] || (string) ($refundRow['refund_status'] ?? '') !== 'SUCCESS') {
            return;
        }

        $orderIdForGuard = trim((string) ($refundRow['order_id'] ?? ''));
        if ($orderIdForGuard !== '') {
            /** @var NormalOrdersRepository $normalOrdersRepository */
            $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $orderRow = $normalOrdersRepository->getInfo([
                'company_id' => $companyId,
                'order_id' => $orderIdForGuard,
            ]);
            if ($orderRow !== []) {
                if ((int) ($orderRow['user_id'] ?? 0) === 0) {
                    Log::channel('shuyun_open_platform')->info('Shuyun refund_sync dispatch skipped: user_id is zero.', [
                        'company_id' => $companyId,
                        'refund_bn' => $refundBn,
                        'order_id' => $orderIdForGuard,
                    ]);

                    return;
                }
                if ((int) ($orderRow['total_fee'] ?? 0) <= 0) {
                    Log::channel('shuyun_open_platform')->info('Shuyun refund_sync dispatch skipped: total_fee is zero (fen).', [
                        'company_id' => $companyId,
                        'refund_bn' => $refundBn,
                        'order_id' => $orderIdForGuard,
                    ]);

                    return;
                }
            }
        }

        $dedupeKey = ShuyunOpenPlatformOrderSyncDispatchCacheKeys::refundSyncDedupeKey($companyId, $refundBn, $lane);
        if (! Cache::add(
            $dedupeKey,
            1,
            ShuyunOpenPlatformOrderSyncDispatchCacheKeys::REFUND_SYNC_DEDUPE_TTL_SEC_PER_LANE
        )) {
            Log::channel('shuyun_open_platform')->info(ShuyunOpenPlatformOrderSyncDispatchCacheKeys::LOG_DISPATCH_DEDUPED, [
                'kind' => 'refund_sync',
                'lane' => $lane,
                'company_id' => $companyId,
                'refund_bn' => $refundBn,
            ]);

            return;
        }

        app(Dispatcher::class)->dispatch(
            (new SyncNormalOrderRefundToShuyunOpenPlatformJob($companyId, $refundBn))->onQueue('slow')
        );
        Log::channel('shuyun_open_platform')->info('Shuyun refund_sync job dispatched.', [
            'company_id' => $companyId,
            'refund_bn' => $refundBn,
            'lane' => $lane,
        ]);
    }

    /**
     * @param  array<string, mixed>  $entities
     */
    private function resolveRefundBn(array $entities): ?string
    {
        $bn = trim((string) ($entities['refund_bn'] ?? ''));
        if ($bn !== '') {
            return $bn;
        }

        if (! isset($entities['cancel_id']) || (int) $entities['cancel_id'] < 1) {
            return null;
        }

        $companyId = (int) ($entities['company_id'] ?? 0);
        $orderId = trim((string) ($entities['order_id'] ?? ''));
        if ($companyId < 1 || $orderId === '') {
            return null;
        }

        $supplierId = (int) ($entities['supplier_id'] ?? 0);

        /** @var AftersalesRefundRepository $refundRepo */
        $refundRepo = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
        $list = $refundRepo->getList([
            'company_id' => $companyId,
            'order_id' => $orderId,
            'supplier_id' => $supplierId,
            'refund_type' => 1,
        ], 0, 1, ['create_time' => 'DESC']);

        $first = $list['list'][0] ?? null;
        if (! is_array($first)) {
            return null;
        }

        $found = trim((string) ($first['refund_bn'] ?? ''));

        return $found !== '' ? $found : null;
    }
}
