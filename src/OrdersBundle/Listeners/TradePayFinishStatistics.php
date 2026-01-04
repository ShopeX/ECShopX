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
use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use OrdersBundle\Traits\GetOrderServiceTrait;

class TradePayFinishStatistics extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;

    protected $queue = 'slow';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        //清空缓存，防止数据不一致
        $em = app('registry')->getManager('default');
        $em->clear();

        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        app('log')->debug('订单号为' . $orderId . '统计开始');
        $sourceType = $event->entities->getTradeSourceType();
        $date = date('Ymd');
        $userData = app('redis')->sadd("companyIds:".$date, $companyId);

        if (in_array($sourceType, ['service', 'groups', 'service_groups', 'service_seckill'])) {
            $statisticsType = 'service';
        } elseif (in_array($sourceType, ['normal', 'normal_normal', 'normal_groups', 'normal_seckill', 'normal_community', 'bargain', 'normal_shopguide', 'normal_pointsmall'])) {
            $statisticsType = 'normal';
        } else {
            app('log')->debug('订单统计异常：订单号为' . $orderId . '，的订单类型' . $sourceType . '暂不统计');
            return true;
        }

        $tradeState = $event->entities->getTradeState();
        $totalFee = $event->entities->getTotalFee();
        $userId = $event->entities->getUserId();
        $shopId = $event->entities->getDistributorId();
        $merchantId = $event->entities->getMerchantId();

        // $orderService = $this->getOrderService($sourceType);
        // $orderdata = $orderService->getOrderInfo($companyId, $orderId);
        // if ($orderdata && isset($orderdata['orderInfo'])) {
        //     $orderdata = $orderdata['orderInfo'];
        // }
        if ($tradeState == 'SUCCESS') {
            $redisKey = $this->__key($companyId, $statisticsType, $date);
            //统计商城订单总支付金额
            app('log')->debug('ajxorderPayFee：'.$orderId.'----->'.$totalFee.'------>'.$userId);
            $newStore = app('redis')->hincrby($redisKey, "orderPayFee", $totalFee);
            //统计商城订单支付订单数
            $newStore = app('redis')->hincrby($redisKey, "orderPayNum", 1);

            //统计商城订单支付会员数
            $userData = app('redis')->sadd($redisKey."_orderPayUser", $userId);

            // if (isset($orderdata['salesman_id']) && $orderdata['salesman_id']) {
            //     $salespersonId = $orderdata['salesman_id'];
            //     $salespersonKey = $this->__salespersonKey($companyId, $statisticsType, $date, $salespersonId);
            //     //统计导购员销售额
            //     app('redis')->hincrby($salespersonKey, $salespersonId."_salesperson_orderPayFee", $totalFee);
            //     //统计导购员销售订单数
            //     app('redis')->hincrby($salespersonKey, $salespersonId."_salesperson_orderPayNum", 1);
            //     //统计导购员销售订单支付会员数
            //     app('redis')->sadd($salespersonKey."_".$salespersonId."_salesperson_orderPayUser", $userId);
            // }

            if ($shopId) {
                //统计店铺订单总金额
                $newStore = app('redis')->hincrby($redisKey, $shopId."_orderPayFee", $totalFee);
                //统计店铺订单支付订单数
                $newStore = app('redis')->hincrby($redisKey, $shopId."_orderPayNum", 1);
                //统计店铺订单支付会员数
                $userData = app('redis')->sadd($redisKey."_".$shopId."_orderPayUser", $userId);
            }

            if ($merchantId) {
                //统计店铺订单总金额
                $newStore = app('redis')->hincrby($redisKey, $merchantId."_merchant_orderPayFee", $totalFee);
                //统计店铺订单支付订单数
                $newStore = app('redis')->hincrby($redisKey, $merchantId."_merchant_orderPayNum", 1);
                //统计店铺订单支付会员数
                $userData = app('redis')->sadd($redisKey."_".$merchantId."_merchant_orderPayUser", $userId);
            }
        }
    }

    private function __key($companyId, $type, $date)
    {
        return "OrderPayStatistics:".$type.":".$companyId.":".$date;
    }

    //导购统计键值
    // private function __salespersonKey($companyId, $type, $date, $salespersonId)
    // {
    //     return "OrderPaySalespersonStatistics:$type:$companyId:SalespersonId:$salespersonId:$date";
    // }
}
