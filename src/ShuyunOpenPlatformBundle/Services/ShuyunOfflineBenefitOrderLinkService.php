<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

use Illuminate\Support\Facades\Log;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendItemRepository;

/**
 * 将数云线下权益发放明细与商城订单关联：下单核销优惠券后写入 local_order_id，供 ConsumePush 推 USED/NOT_USED。
 */
final class ShuyunOfflineBenefitOrderLinkService
{
    public const LOG_CHANNEL = 'shuyun_open_platform';

    public function __construct(
        private ShuyunOfflineBenefitSendItemRepository $sendItemRepository,
    ) {
    }

    /**
     * 幂等：仅当存在未关联且发券成功的明细时写入 local_order_id。
     */
    public function linkCouponToOrder(int $companyId, int $orderId, int $userId, string $couponCode): void
    {
        $code = trim($couponCode);
        if ($code === '' || $orderId <= 0 || $userId <= 0) {
            return;
        }

        try {
            $item = $this->sendItemRepository->findOneUnlinkedSuccessByCompanyUserAndBenefitCode($companyId, $userId, $code);
            if ($item === null) {
                return;
            }
            $item->setLocalOrderId($orderId);
            $this->sendItemRepository->save($item);
        } catch (\Throwable $e) {
            Log::channel(self::LOG_CHANNEL)->warning('Shuyun offline benefit: link coupon to order failed.', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
