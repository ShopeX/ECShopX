<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Listeners;

use OrdersBundle\Events\TradeFinishEvent;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitOrderLinkService;

/**
 * 支付完成（{@see TradeFinishEvent}）且与 {@see OrdersBundle\Listeners\TradeFinishConsumeCard} 一样处理优惠券核销后，
 * 将券码与 {@see ShuyunOfflineBenefitSendItem} 关联到订单，便于数云核销回推。
 *
 * 须在 EventProvider 中注册在 {@see OrdersBundle\Listeners\TradeFinishConsumeCard} **之后**。
 */
final class ShuyunOfflineBenefitLinkSendItemOnTradeFinishListener
{
    public function handle(TradeFinishEvent $event): void
    {
        $entities = $event->entities;
        $discountInfo = $entities->getDiscountInfo();
        if ($discountInfo === null || $discountInfo === '') {
            return;
        }
        if (\is_array($discountInfo)) {
            $rows = $discountInfo;
        } else {
            $rows = json_decode((string) $discountInfo, true);
            if (!\is_array($rows)) {
                return;
            }
        }

        $companyId = (int) $entities->getCompanyId();
        $orderId = (int) $entities->getOrderId();
        $userId = (int) $entities->getUserId();

        $link = app(ShuyunOfflineBenefitOrderLinkService::class);
        foreach ($rows as $row) {
            if (!\is_array($row) || !isset($row['coupon_code']) || $row['coupon_code'] === '') {
                continue;
            }
            $link->linkCouponToOrder($companyId, $orderId, $userId, (string) $row['coupon_code']);
        }
    }
}
