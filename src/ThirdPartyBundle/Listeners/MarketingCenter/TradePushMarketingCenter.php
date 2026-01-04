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

namespace ThirdPartyBundle\Listeners\MarketingCenter;

use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Services\TradeService;
use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;
use ThirdPartyBundle\Services\MarketingCenter\SalespersonAndShop;

class TradePushMarketingCenter
{
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param TradeFinishEvent $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfoById($event->entities->getTradeId());
        if (!$tradeInfo) {
            return true;
        }
        foreach ($tradeInfo as $key => &$value) {
            if (is_int($value)) {
                $value = strval($value);
            }
        }
        unset($value);
        $orderService = $this->getOrderService($tradeInfo['trade_source_type']);
        $orderInfo = $orderService->getOrderInfo($tradeInfo['company_id'], $tradeInfo['order_id']);
        if (empty($orderInfo) || empty($orderInfo['orderInfo']['salesman_id'])) {
            return true;
        }
        $input['trade_id'] = $tradeInfo['trade_id'] ?? '';
        $input['chat_id'] = $orderInfo['orderInfo']['chat_id'] ?? '';
        $input['order_id'] = $tradeInfo['order_id'] ?? '';
        $input['company_id'] = $tradeInfo['company_id'] ?? '';
        $input['shop_id'] = $tradeInfo['shop_id'] ?? '';
        $input['distributor_id'] = $tradeInfo['distributor_id'] ?? '';
        $input['external_member_id'] = $tradeInfo['user_id'];
        $input['mobile'] = $tradeInfo['mobile'] ?? '';
        $input['discount_info'] = $tradeInfo['discount_info'] ?? '';
        $input['mch_id'] = $tradeInfo['mch_id'] ?? '';
        $input['total_fee'] = $tradeInfo['total_fee'] ?? '';
        $input['discount_fee'] = $tradeInfo['discount_fee'] ?? '';
        $input['pay_fee'] = $tradeInfo['pay_fee'] ?? '';
        switch ($tradeInfo['pay_type']) {
            case 'wxpay': $pay_type = '1';
                break;
            case 'deposit': $pay_type = '2';
                break;
            case 'pos': $pay_type = '3';
                break;
            case 'point': $pay_type = '4';
                break;
            default: $pay_type = '1'; //默认现金支付
        }
        $input['pay_type'] = $pay_type;
        $input['transaction_id'] = $tradeInfo['transaction_id'] ?? '';
        $input['pay_time'] = date('Y-m-d H:i:s', $tradeInfo['time_expire'] ?? '');
        $input['coupon_fee'] = $tradeInfo['coupon_fee'] ?? '';
        $input['coupon_info'] = $tradeInfo['coupon_info'] ?? '';
        $input['order_source'] = '1';

        $salespersonAndShop = new SalespersonAndShop();
        $input = $salespersonAndShop->formatSalesData($tradeInfo['company_id'], $orderInfo['orderInfo'], $input);
        if (!$input) {
            return true;
        }

        foreach ($input as &$value) {
            if (is_int($value)) {
                $value = strval($value);
            }
            if (is_null($value)) {
                $value = '';
            }
            if (is_array($value) && empty($value)) {
                $value = '';
            }
        }
        $request = new Request();
        $request->call($tradeInfo['company_id'], 'basics.order.pay', $input);
    }
}
