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

namespace OrdersBundle\Jobs;

use EspierBundle\Jobs\Job;

use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Traits\GetOrderServiceTrait;

class TradeRefundStatistics extends Job
{
    use GetOrderServiceTrait;

    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // TODO: optimize this method
        if (!is_array($this->data)) {
            app('log')->debug('订单退款统计异常：数据有误');
            return true;
        }
        $companyId = $this->data['company_id'];
        $orderId = $this->data['order_id'];

        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $orderId);
        $result = [];
        if ($order) {
            $orderService = $this->getOrderServiceByOrderInfo($order);
            $result = $orderService->getOrderInfo($companyId, $orderId);
        }
        if ($result && isset($result['orderInfo'])) {
            $orderdata = $result['orderInfo'];
        }
        if ($result && isset($result['tradeInfo'])) {
            $tradedata = $result['tradeInfo'];
        }

        // if (in_array($orderdata['order_status'], ['REFUND_SUCCESS', 'CANCEL', 'WAIT_BUYER_CONFIRM'])) {
        $sourceType = $tradedata['tradeSourceType'];

        $date = date('Ymd');
        $userData = app('redis')->sadd("companyIds:".$date, $companyId);

        if (in_array($sourceType, ['service', 'groups', 'service_groups', 'service_seckill'])) {
            $statisticsType = 'service';
        } elseif (in_array($sourceType, ['normal', 'normal_groups', 'normal_seckill', 'normal_community', 'bargain'])) {
            $statisticsType = 'normal';
        } else {
            app('log')->debug('订单统计异常：订单号为'.$orderId.'，的订单类型'.$sourceType. '暂不统计');
            return true;
        }

        $redisKey = $this->__key($companyId, $statisticsType, $date);
        //统计商城订单总退款金额
        if ($this->data['refund_fee'] ?? 0) {
            $refundFee = $this->data['refund_fee'];
        } else {
            $refundFee = $this->data['pay_fee'];
        }
        $newStore = app('redis')->hincrby($redisKey, "orderRefundFee", $refundFee);
        if (!$newStore) {
            app('log')->debug('订单统计异常：订单号为'.$orderId.'，的订单类型'.$sourceType. '暂不统计');
            return true;
        }
        if (isset($orderdata['distributor_id'])) {
            $shopId = $orderdata['distributor_id'];
            //统计店铺订单总金额
            $newStore = app('redis')->hincrby($redisKey, $shopId."_orderRefundFee", $refundFee);
        }
        if (!empty($orderdata['merchant_id'])) {
            $merchantId = $orderdata['merchant_id'];
            //统计商户订单退款总金额
            $newStore = app('redis')->hincrby($redisKey, $merchantId."_merchant_orderRefundFee", $refundFee);
        }
        // }
        return true;
    }

    private function __key($companyId, $type, $date)
    {
        // TODO: optimize this method
        return "OrderPayStatistics:".$type.":".$companyId.":".$date;
    }
}
