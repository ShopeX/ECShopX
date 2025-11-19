<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Listeners;

use OrdersBundle\Events\TradeFinishEvent;
use KaquanBundle\Services\UserDiscountService;

class TradeFinishConsumeCard
{
    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        // 积分支付订单不需要
        if (in_array($event->entities->getPayType(), ['point', 'deposit'])) {
            return true;
        }

        $discountInfo = $event->entities->getDiscountInfo();
        $discountInfo = json_decode($discountInfo, true);
        $companyId = $event->entities->getCompanyId();

        try {
            if ($discountInfo) {
                $wechatCardService = new UserDiscountService();
                foreach ($discountInfo as $row) {
                    if (isset($row['coupon_code'])) {
                        $code = $row['coupon_code'];

                        $params['consume_outer_str'] = '买单核销';
                        $params['trans_id'] = $event->entities->getOrderId();
                        $params['fee'] = $event->entities->getPayFee();
                        $params['shop_id'] = $event->entities->getShopId();

                        $wechatCardService->userConsumeCard($companyId, $code, $params);
                    }
                }
            }
        } catch (\Exception $e) {
            app('log')->debug('核销优惠券'. $e->getMessage());
        }
    }
}
