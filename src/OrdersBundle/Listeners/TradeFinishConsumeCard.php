<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
