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

namespace ThirdPartyBundle\Services\ShopexCrm;

use GoodsBundle\Entities\Items;
use MembersBundle\Entities\MembersInfo;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use SuperAdminBundle\Entities\Logistics;

class SyncSingleOrderService
{
    private $apiName = 'order/syncSingleOrder';

    public $ordersNormalReposity;

    public $ordersNormalItemsReposity;

    public $logisticsReposity;
    public $itemsRepository;
    public $membersInfoRepository;

    public function __construct()
    {
        $this->ordersNormalReposity = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->ordersNormalItemsReposity = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $this->logisticsReposity = app('registry')->getManager('default')->getRepository(Logistics::class);
        $this->itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        $this->membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
    }

    public function syncSingleOrder($company_id, $order_id)
    {
        $ordersNormal = $this->ordersNormalReposity->getInfo(['company_id' => $company_id, 'order_id' => $order_id]);
        if ($ordersNormal['pay_status'] != 'PAYED') {
            return false;
        }

        $orderNormalItems = $this->ordersNormalItemsReposity->getList(['company_id' => $company_id, 'order_id' => $order_id]);
        $memberInfo = $this->membersInfoRepository->getInfo(['company_id' => $company_id, 'user_id' => $ordersNormal['user_id']]);
        $data['platform_id'] = 'shopex';
        $data['unique_id'] = $ordersNormal['order_id'];
        $data['source'] = 'custom_source1';
        $data['created'] = date('Y-m-d H:i:s', $ordersNormal['create_time']);
        ## 订单类型
        $data['order_type'] = 'OrderType01';
        $data['point_account_code'] = '';
        $data['lastmodify'] = date('Y-m-d H:i:s', $ordersNormal['update_time']);
        $data['status'] = '交易成功';
        $data['payment_status'] = '已付款';
        $ship = ['DONE' => '全部发货', 'PENDING' => '未发货', 'PARTAIL' => '部分发货'];
        $data['ship_status'] = $ship[$ordersNormal['delivery_status']];
        $data['payment_type'] = $ordersNormal['pay_type'];
        if (!empty($ordersNormal['delivery_corp'])) {
            $logistics = $this->logisticsReposity->getInfo(['kuaidi_code' => $ordersNormal['delivery_corp']]);
        }
        $data['shipping_type'] = $logistics['full_name'] ?? '';
        $data['total_goods_fee'] = bcdiv($ordersNormal['item_fee'], 100, 2);
        $data['total_trade_fee'] = bcdiv($ordersNormal['freight_fee'], 100, 2);
        $data['total_amount'] = bcdiv($ordersNormal['total_fee'], 100, 2);
        $data['buyer_uname'] = $ordersNormal['receiver_name'];
        $data['buyer_id'] = $ordersNormal['user_id'];
        $data['buyer_name'] = $ordersNormal['receiver_name'];
        $data['buyer_email'] = $memberInfo['email'] ?? '';
        $data['buyer_mobile'] = $ordersNormal['receiver_mobile'];
        $data['buyer_province'] = $ordersNormal['receiver_state'];
        $data['buyer_city'] = $ordersNormal['receiver_city'];
        $data['buyer_district'] = $ordersNormal['receiver_district'];
        $data['buyer_address'] = $ordersNormal['receiver_address'];
        $data['orders'] = [];
        foreach ($orderNormalItems['list'] as $key => $value) {
            $items = $this->itemsRepository->getInfo(['item_id' => $value['item_id']]);
            $goods['name'] = $value['item_name'];
            $goods['num'] = $value['num'];
            $goods['total_order_fee'] = bcdiv($value['item_fee'], 100, 2);
            $goods['sku_id'] = $items['item_bn'];
            $goods['bn'] = $items['item_bn'];
            $goods['barcode'] = $items['barcode'];
            $goods['price'] = bcdiv($items['price'], 100, 2);
            $goods['sale_price'] = bcdiv($value['item_fee'] / $value['num'], 100, 2);
            $data['orders'][] = $goods;
        }
        $request = new Request();
        $result = $request->sendRequest($this->apiName, $data);
        return $result;
    }
}
