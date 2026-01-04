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

use AftersalesBundle\Entities\Aftersales;
use AftersalesBundle\Entities\AftersalesDetail;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use SystemLinkBundle\Events\TradeAftersalesCancelEvent;
use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;
use SalespersonBundle\Entities\ShopSalesperson;
use DistributionBundle\Entities\Distributor;

class TradeAftersalesCancelPushMarketingCenter
{
    // ID: 53686f704578
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param TradeAftersalesCancelEvent $event
     * @return void
     */
    public function handle(TradeAftersalesCancelEvent $event)
    {
        // ID: 53686f704578
        $company_id = $event->entities['company_id'];
        $order_id = $event->entities['order_id'];
        $aftersalesBn = $event->entities['aftersales_bn'];

        $aftersalesRepository = app('registry')->getManager('default')->getRepository(Aftersales::class);
        $aftersalesInfo = $aftersalesRepository->get(['company_id' => $company_id, 'order_id' => $order_id,'aftersales_bn' => $aftersalesBn]);

        $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderInfo = $normalOrderRepository->getInfo(['company_id' => $company_id, 'order_id' => $order_id]);
        if (!$aftersalesInfo || !$orderInfo || empty($orderInfo['salesman_id'])) {
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
        $input['external_member_id'] = $orderInfo['user_id'];
        $input['aftersales_bn'] = $aftersalesInfo['aftersales_bn'];
        $input['order_id'] = $aftersalesInfo['order_id'];
        $input['aftersales_type'] = $aftersalesInfo['aftersales_type'];
        if ($aftersalesInfo['aftersales_status'] > 0) {
            $input['aftersales_status'] = $aftersalesInfo['aftersales_status'] - 1;
        } else {
            $input['aftersales_status'] = $aftersalesInfo['aftersales_status'];
        }
//        $input['progress'] = $aftersalesInfo['progress'];
        $input['refund_fee'] = $aftersalesInfo['refund_fee'];
        $input['reason'] = $aftersalesInfo['reason'];
        $input['description'] = $aftersalesInfo['description'];
        $input['evidence_pic'] = $aftersalesInfo['evidence_pic'];
        $input['refuse_reason'] = $aftersalesInfo['refuse_reason'];
        $input['apply_time'] = date('Y-m-d H:i:s', $aftersalesInfo['create_time']);


        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);

        $aftersalesDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class);
        $aftersalesDetail = $aftersalesDetailRepository->getList(['company_id' => $company_id, 'aftersales_bn' => $aftersalesBn]);
        foreach ($aftersalesDetail['list'] as $key => $value) {
            $input['items'][$key]['sub_order_id'] = $value['sub_order_id'];
            $input['items'][$key]['item_id'] = $value['item_id'];
            $input['items'][$key]['item_bn'] = $value['item_bn'];
            $input['items'][$key]['item_name'] = $value['item_name'];
            $input['items'][$key]['item_pic'] = $value['item_pic'];
            $input['items'][$key]['num'] = $value['num'];
            $input['items'][$key]['refund_fee'] = $value['refund_fee'];
            $normalOrdersItemsInfo = $normalOrdersItemsRepository->getList(['company_id' => $company_id, 'order_id' => $order_id,'item_id' => $value['item_id']]);
            if ($normalOrdersItemsInfo) {
                $input['items'][$key]['item_spec_desc'] = $normalOrdersItemsInfo['list'][0]['item_spec_desc'];
            }
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
        foreach ($input['items'] as &$items) {
            foreach ($items as &$item) {
                if (is_int($item)) {
                    $item = strval($item);
                }
                if (is_null($item)) {
                    $item = '';
                }
            }
        }

        $params[0] = $input;
        $request = new Request();
        $request->call($company_id, 'basics.aftersales.proccess', $params);
    }
}
