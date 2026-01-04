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
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Events\NormalOrderAddEvent;
use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;
use ThirdPartyBundle\Services\MarketingCenter\SalespersonAndShop;

class OrderAddPushMarketingCenter
{
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param NormalOrderAddEvent $event
     * @return void
     */
    public function handle(NormalOrderAddEvent $event)
    {
        // 53686f704578
        $company_id = $event->entities['company_id'];
        $order_id = $event->entities['order_id'];
        $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderInfo = $normalOrderRepository->getInfo(['company_id' => $company_id, 'order_id' => $order_id]);

        if (!$orderInfo || empty($orderInfo['salesman_id'])) {
            return true;
        }


        $input['order_id'] = $orderInfo['order_id'];
        $input['order_title'] = $orderInfo['title'];
        $input['mobile'] = $orderInfo['mobile'];
        $input['external_member_id'] = $orderInfo['user_id'];
        $input['total_fee'] = $orderInfo['total_fee'];
        $input['freight_fee'] = $orderInfo['freight_fee'];
        $input['item_fee'] = $orderInfo['item_fee'];
//        $input['cash_discount'] = $orderInfo['coupon_discount'];
        $input['cost_fee'] = $orderInfo['cost_fee'];
        $input['receipt_type'] = $orderInfo['receipt_type'];
        $input['ziti_status'] = $orderInfo['ziti_status'];
        $input['delivery_status'] = $orderInfo['delivery_status'];
        $input['cancel_status'] = $orderInfo['cancel_status'];
        $input['total_rebate'] = $orderInfo['total_rebate'];
        $input['discount_info'] = $orderInfo['discount_info'];
        $input['chat_id'] = $orderInfo['chat_id'];
        $input['order_create_time'] = date('Y-m-d H:i:s', $orderInfo['create_time']);
        $input['receiver_zip'] = $orderInfo['receiver_zip'];
        $input['receiver_province'] = $orderInfo['receiver_state'];
        $input['receiver_city'] = $orderInfo['receiver_city'];
        $input['receiver_county'] = $orderInfo['receiver_district'];
        $input['receiver_address'] = $orderInfo['receiver_address'];
        $input['receiver_name'] = $orderInfo['receiver_name'];
        $input['receiver_mobile'] = $orderInfo['receiver_mobile'];
        $input['pay_type'] = $orderInfo['pay_type'];
        $input['order_source'] = '1';
        $input['pay_status'] = $orderInfo['pay_status'];
        $input['order_status_des'] = $orderInfo['order_status'];
        $input['third_params'] = $orderInfo['third_params'];
        switch ($input['order_status_des']) {
            case 'DONE':
                $statusMsg = '订单完成';
                break;
            case 'NOTPAY':
                $statusMsg = '未支付';
                break;
            case 'PART_PAYMENT':
                $statusMsg = '部分付款';
                break;
            case 'WAIT_GROUPS_SUCCESS':
                $statusMsg = '等待拼团成功';
                break;
            case 'PAYED':
                $statusMsg = '已支付';
                break;
            case 'CANCEL':
                $statusMsg = '已取消';
                break;
            case 'WAIT_BUYER_CONFIRM':
                $statusMsg = '待用户收货';
                break;
            case 'REFUND_SUCCESS':
                $statusMsg = '退款成功';
                break;
        }
        $input['order_status_msg'] = $statusMsg ?? '';
        $input['remark'] = $orderInfo['remark'];

        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $orderItem = $normalOrdersItemsRepository->get($company_id, $order_id);
        foreach ($orderItem as $key => $value) {
            $input['items'][$key]['sub_order_id'] = $value['id'];
            $input['items'][$key]['price'] = $value['price'];
            $input['items'][$key]['cost_fee'] = $value['cost_fee'];
            $input['items'][$key]['item_fee'] = $value['item_fee'];
            $input['items'][$key]['item_bn'] = $value['item_bn'];
            $input['items'][$key]['item_spec_desc'] = $value['item_spec_desc'];
            $input['items'][$key]['total_fee'] = $value['total_fee'];
            $input['items'][$key]['member_discount'] = $value['member_discount'];
            $input['items'][$key]['coupon_discount'] = $value['coupon_discount'];
            $input['items'][$key]['discount_fee'] = $value['discount_fee'];
            $input['items'][$key]['num'] = $value['num'];
            if ($value['order_item_type'] == 'gift') {
                $input['items'][$key]['item_type'] = '1';
            } else {
                $input['items'][$key]['item_type'] = '0';
            }
            $input['items'][$key]['item_name'] = $value['item_name'];
            $input['items'][$key]['item_pic'] = $value['pic'];
            $input['items'][$key]['profit_amount'] = '0';
            $input['items'][$key]['order_status'] = '1';
        }
        $salespersonAndShop = new SalespersonAndShop();
        $input = $salespersonAndShop->formatSalesData($company_id, $orderInfo, $input);
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
        $request->call($company_id, 'basics.order.proccess', $params);
    }
}
