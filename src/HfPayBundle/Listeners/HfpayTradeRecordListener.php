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

namespace HfPayBundle\Listeners;

use HfPayBundle\Services\HfpayTradeRecordService;

class HfpayTradeRecordListener
{
    /**
     * 支付成功事件
     */
    public function paySuccess($event)
    {
        $order_id = $event->entities->getOrderId();
        $service = new HfpayTradeRecordService();
        $service->paySuccess($order_id);

        return true;
    }

    /**
     * 佣金结算事件
     */
    public function brokerage($event)
    {
        $order_id = $event->entities['order_id'];
        $service = new HfpayTradeRecordService();
        $service->brokerage($order_id);

        return true;
    }

    /**
     * 退款成功事件
     */
    public function refundSuccess($event)
    {
        $order_id = $event->entities['order_id'];
        $refund_bn = $event->entities['refund_bn'];
        $service = new HfpayTradeRecordService();
        $service->refundSuccess($order_id, $refund_bn);

        return true;
    }

    /**
     * 分账完成事件
     */
    public function profit($event)
    {
        $order_id = $event->entities['order_id'];
        $service = new HfpayTradeRecordService();
        $service->profit($order_id);

        return true;
    }

    /**
     * 提现完成事件
     */
    public function withdraw($event)
    {
        $company_id = $event->entities['company_id'];
        $distributor_id = $event->entities['distributor_id'];
        $trans_amt = $event->entities['trans_amt'];
        $order_id = $event->entities['order_id'];
        $service = new HfpayTradeRecordService();
        $service->withdraw($company_id, $distributor_id, $trans_amt, $order_id);

        return true;
    }

    /**
     * 为订阅者注册监听器
     */
    public function subscribe($events)
    {
        //支付成功
        $events->listen(
            'OrdersBundle\Events\TradeFinishEvent',
            'HfPayBundle\Listeners\HfpayTradeRecordListener@paySuccess'
        );

        //佣金计算
        $events->listen(
            'HfPayBundle\Events\HfpayBrokerageEvent',
            'HfPayBundle\Listeners\HfpayTradeRecordListener@brokerage'
        );

        //退款成功
        $events->listen(
            'HfPayBundle\Events\HfpayRefundSuccessEvent',
            'HfPayBundle\Listeners\HfpayTradeRecordListener@refundSuccess'
        );

        //分账成功
        $events->listen(
            'HfPayBundle\Events\HfpayProfitSharingEvent',
            'HfPayBundle\Listeners\HfpayTradeRecordListener@profit'
        );

        //提现
        $events->listen(
            'HfPayBundle\Events\HfPayDistributorWithdrawSuccessEvent',
            'HfPayBundle\Listeners\HfpayTradeRecordListener@withdraw'
        );
    }
}
