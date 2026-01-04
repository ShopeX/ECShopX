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

use OrdersBundle\Entities\NormalOrders;
use ThirdPartyBundle\Events\TradeRefundFinishEvent;
use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;
use SalespersonBundle\Entities\ShopSalesperson;
use DistributionBundle\Entities\Distributor;
use AftersalesBundle\Entities\Aftersales;

class TradeRefundFinishPushMarketingCenter
{
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param TradeRefundFinishEvent $event
     * @return void
     */
    public function handle(TradeRefundFinishEvent $event)
    {
//        var_dump($event->entities['aftersales_bn']);
        $company_id = $event->entities['company_id'];
        $order_id = $event->entities['order_id'];

        $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderInfo = $normalOrderRepository->getInfo(['company_id' => $company_id, 'order_id' => $order_id]);
        if (!$orderInfo || empty($orderInfo['salesman_id'])) {
            return true;
        }
        $shopsales = app('registry')->getManager('default')->getRepository(ShopSalesperson::class);
        $salesmanInfo = $shopsales->getInfoById($orderInfo['salesman_id']);
        $bindSalesmanInfo = $shopsales->getInfoById($orderInfo['bind_salesman_id']);
//        if ($salesmanInfo) {
        $input['sale_salesperson_id'] = $salesmanInfo['work_userid'] ?? '0';
//        }
//        if ($bindSalesmanInfo) {
        $input['bind_salesperson_id'] = $bindSalesmanInfo['work_userid'] ?? '0';
//        }

        $distributor = app('registry')->getManager('default')->getRepository(Distributor::class);
        $saleInfo = $distributor->getInfoById($orderInfo['sale_salesman_distributor_id']);
        if ($saleInfo) {
            $input['sale_store_bn'] = $saleInfo['shop_code'] ?? '';
        }
        $bindInfo = $distributor->getInfoById($orderInfo['bind_salesman_distributor_id']);
        if ($bindInfo) {
            $input['bind_store_bn'] = $bindInfo['shop_code'] ?? '';
        }

        $input['chat_id'] = $orderInfo['chat_id'];
        $input['refund_bn'] = $event->entities['refund_bn'] ?? '';
        $input['trade_id'] = $event->entities['trade_id'] ?? '';
        $input['external_member_id'] = $orderInfo['user_id'];
        $input['aftersales_bn'] = $event->entities['aftersales_bn'] ?? '';
        $input['order_id'] = $event->entities['order_id'] ?? '';
        $input['company_id'] = $event->entities['company_id'] ?? '';
        $input['shop_id'] = $event->entities['shop_id'] ?? '';
        $input['distributor_id'] = $event->entities['distributor_id'] ?? '';

        $input['refund_fee'] = $event->entities['refund_fee'] ?? '';
        $input['refunded_fee'] = $event->entities['refunded_fee'] ?? '';
        $input['refund_success_time'] = date('Y-m-d H:i:s', $event->entities['refund_success_time'] ?? '');

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
        $request->call($company_id, 'basics.aftersales.refund', $input);
    }
}
