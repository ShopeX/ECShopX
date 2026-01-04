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

namespace OpenapiBundle\Services\Order;

use OpenapiBundle\Services\BaseService;

use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\OrdersDelivery;
use OrdersBundle\Entities\OrdersDeliveryItems;

class DeliveryService extends BaseService
{
    public function getEntityClass(): string
    {
        return OrdersDelivery::class;
    }

    /**
     * @return mixed
     *
     * 发货单列表及商品
     */
    public function getDeliveryItems($params)
    {
        $company_id = $params['company_id'];
        $order_id = $params['order_id'];
        $filter = [
            'company_id' => $company_id,
            'order_id' => $order_id
        ];
        $ordersDeliveryRepository = app('registry')->getManager('default')->getRepository(OrdersDelivery::class);
        $delivery_list = $ordersDeliveryRepository->getLists($filter);
        $data = [];
        $delivery_num = 0;
        if (!empty($delivery_list)) {
            $ordersDeliveryItemsRepository = app('registry')->getManager('default')->getRepository(OrdersDeliveryItems::class);
            foreach ($delivery_list as $val) {
                $items_num = 0;
                $items = [];
                $filter = [
                    'orders_delivery_id' => $val['orders_delivery_id']
                ];
                $delivery_items_list = $ordersDeliveryItemsRepository->getLists($filter);
                foreach ($delivery_items_list as $_val) {
                    $items[] = [
                        'order_items_id' => $_val['order_items_id'],
                        'item_id' => $_val['item_id'],
                        'num' => $_val['num'],
                        'item_name' => $_val['item_name'],
                        'pic' => $_val['pic'],
                        // 'num' => $_val['num'],
                    ];

                    $items_num += $_val['num'];
                }

                // $delivery_info = $this->deliveryInfo($val['orders_delivery_id']);
                $data[] = [
                    'delivery_id' => $val['orders_delivery_id'],
                    'delivery_corp' => $val['delivery_corp'],
                    'delivery_corp_name' => $val['delivery_corp_name'],
                    'delivery_code' => $val['delivery_code'],
                    'delivery_time' => date('Y-m-d H:i:s', $val['delivery_time']),
                    'items' => $items,
                    'items_num' => $items_num,
                    'status_msg' => '已发货',
                    'status' => 1,
                    // 'delivery_info' => $delivery_info[0]['AcceptStation'] ?? ''
                ];

                $delivery_num++;
            }
        }

        //未发货的商品
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        
        $orders_items = $normalOrdersItemsRepository->get($company_id, $order_id);
        $items = [];
        $items_num = 0;
        foreach ($orders_items as $orders_items_val) {
            if ($orders_items_val['delivery_status'] != 'DONE') {
                $num = $orders_items_val['num'] - $orders_items_val['cancel_item_num'] - $orders_items_val['delivery_item_num'];
                if ($num <= 0) {
                    continue;
                }

                $items[] = [
                    'order_items_id' => $orders_items_val['id'],
                    'item_id' => $orders_items_val['item_id'],
                    'num' => $orders_items_val['num'],
                    'item_name' => $orders_items_val['item_name'],
                    'pic' => $orders_items_val['pic'],
                    // 'num' => $orders_items_val['num'],
                ];

                $items_num += $num;
            }
        }

        if (!empty($items)) {
            $data[] = [
                'delivery_id' => '',
                'delivery_corp' => '',
                'delivery_corp_name' => '',
                'delivery_code' => '',
                'delivery_time' => '',
                'items' => $items,
                'items_num' => $items_num,
                'status_msg' => '未发货',
                'status' => 0,
                // 'delivery_info' => ''
            ];
        }

        return [
            'delivery_num' => $delivery_num,
            'list' => $data,
        ];
    }
}
