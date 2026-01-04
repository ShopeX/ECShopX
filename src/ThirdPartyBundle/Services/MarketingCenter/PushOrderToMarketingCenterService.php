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

namespace ThirdPartyBundle\Services\MarketingCenter;

use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Entities\Trade;
use SalespersonBundle\Entities\ShopSalesperson;
use DistributionBundle\Entities\Distributor;
use AftersalesBundle\Entities\Aftersales;
use AftersalesBundle\Entities\AftersalesDetail;
use AftersalesBundle\Entities\AftersalesRefund;
use Dingo\Api\Exception\ResourceException;

class PushOrderToMarketingCenterService
{
    /**
     * 1、create:订单创建，只推送未支付订单
     * 2、payed:订单支付，推送支付单、订单支付状态和时间
     * 3、cancel:订单取消，未支付取消，推订单取消状态
     * 4、delivery:订单发货，推送订单发货状态
     * 5、finish:订单完成，推送订单完成
     * 6、refund:订单退款，推送退款单和订单取消or完成状态
     */
    private $orderStatus = [
        'NOTPAY' => 'create',
        'PAYED' => 'payed',
        'CANCEL' => 'cancel',
        'WAIT_BUYER_CONFIRM' => 'delivery',
        'DONE' => 'finish',
        'REFUND_SUCCESS' => 'refund',
    ];

    public function pushOrder($companyId, $orderId, $isGetNewRelData = true)
    {
        //获取订单信息
        $order = $this->getOrderData($companyId, $orderId);

        $guideData = [
            'sale' => [
                'person_id' => $order['sale_salesperson_id'],
                'store_id' => $order['sale_store_bn'],
            ],
            'bind' => [
                'person_id' => $order['bind_salesperson_id'],
                'store_id' => $order['bind_store_bn'],
            ]
        ];
        //获取销售导购和绑定导购相关信息
        $salesperson = $this->getShopSalesperson($companyId, $orderId, $guideData, $isGetNewRelData);

        $orderUpdate = [];
        foreach ($salesperson as $key => $data) {
            if ($data['new_store_id'] ?? null) {
                $orderUpdate[$key."_salesman_distributor_id"] = $data['new_store_id'];
            }
            $order[$key.'_salesperson_id'] = $data['person_code'];
            $order[$key.'_store_bn'] = $data['store_code'];
        }

        //订单销售导购和绑定导购与门店的关系异常时，更新成最新的绑定关系（数据来源营销中心导购和门店的关系绑定）
        if (!empty($orderUpdate)) {
            $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $result = $normalOrderRepository->update(['company_id' => $companyId, 'order_id' => $orderId], $orderUpdate);
            if (!$result) {
                throw new ResourceException($orderId.'更新订单销售门店或绑定门店失败');
            }
        }

        //订单不是支付状态时，请求支付、退款、售后等信息
        if ($order['order_status_des'] != 'NOTPAY') {
            //获取支付单信息
            $order['trade_data'] = $this->getTrade($companyId, $orderId);

            //获取退款单信息
            $aftersalesBns = [];
            $order['refund_data'] = $this->getRefund($companyId, $orderId, $aftersalesBns);

            //售后退款获取售后单明细
            $order['aftersales_data'] = $this->getAftersale($companyId, $orderId, $aftersalesBns);
        }
        $request = new Request();
        $request->call($companyId, 'basics.order', $order);
        return true;
    }

    private function getOrderData($companyId, $orderId)
    {
        $orderData = [];
        $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderInfo = $normalOrderRepository->getInfo(['company_id' => $companyId, 'order_id' => $orderId]);
        if (!$orderInfo) {
            throw new \Exception($orderId.'订单不存在');
        }

        //无销售导购不推单
        if (empty($orderInfo['salesman_id'])) {
            throw new \Exception($orderId.'订单未存在销售导购，不推单至营销中心');
        }

        //零元订单不推单（积分全额抵扣、）
        if (intval($orderInfo['total_fee']) == 0) {
            throw new \Exception($orderId.'零元订单不推送至营销中心');
        }

        $orderData['order_id'] = (string)$orderInfo['order_id'];
        $orderData['order_title'] = (string)$orderInfo['title'];
        $orderData['mobile'] = (string)$orderInfo['mobile'];
        $orderData['total_fee'] = (string)$orderInfo['total_fee'];
        $orderData['freight_fee'] = (string)$orderInfo['freight_fee'];
        $orderData['item_fee'] = (string)$orderInfo['item_fee'];
        $orderData['cost_fee'] = (string)$orderInfo['cost_fee'];
        $orderData['receipt_type'] = (string)$orderInfo['receipt_type'];
        $orderData['ziti_status'] = (string)$orderInfo['ziti_status'];
        $orderData['delivery_status'] = (string)$orderInfo['delivery_status'];
        $orderData['cancel_status'] = (string)$orderInfo['cancel_status'];
        $orderData['total_rebate'] = (string)$orderInfo['total_rebate'];
        $orderData['chat_id'] = (string)$orderInfo['chat_id'];
        $orderData['receiver_zip'] = (string)$orderInfo['receiver_zip'];
        $orderData['receiver_province'] = (string)$orderInfo['receiver_state'];
        $orderData['receiver_city'] = (string)$orderInfo['receiver_city'];
        $orderData['receiver_county'] = (string)$orderInfo['receiver_district'];
        $orderData['receiver_address'] = (string)$orderInfo['receiver_address'];
        $orderData['receiver_name'] = (string)$orderInfo['receiver_name'];
        $orderData['receiver_mobile'] = (string)$orderInfo['receiver_mobile'];
        $orderData['pay_type'] = (string)$orderInfo['pay_type'];
        $orderData['pay_status'] = (string)$orderInfo['pay_status'];
        $orderData['order_status_des'] = (string)$orderInfo['order_status'];
        $orderData['order_status_msg'] = (string)$this->getOrderStatus($orderData['order_status_des']);
        $orderData['remark'] = (string)$orderInfo['remark'];
        $orderData['sale_salesperson_id'] = (string)$orderInfo['salesman_id'] ?? '0';
        $orderData['bind_salesperson_id'] = (string)$orderInfo['bind_salesman_id'] ?? '0';
        $orderData['sale_store_bn'] = (string)$orderInfo['sale_salesman_distributor_id'] ?? '';
        $orderData['bind_store_bn'] = (string)$orderInfo['bind_salesman_distributor_id'] ?? '';
        $orderData['order_create_time'] = date('Y-m-d H:i:s', $orderInfo['create_time']);
        $orderData['end_time'] = null;
        $orderData['external_member_id'] = '0';
        $orderData['order_source'] = '1';
        $orderData['discount_info'] = is_array($orderInfo['discount_info']) ? json_encode($orderInfo['discount_info']) : $orderInfo['discount_info'];
        if (!empty($orderInfo['end_time']) && $orderInfo['order_status'] == 'DONE') {
            $orderData['end_time'] = date('Y-m-d H:i:s', $orderInfo['end_time']);
        }

        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $orderItem = $normalOrdersItemsRepository->get($companyId, $orderId);
        foreach ($orderItem as $key => $value) {
            $orderData['items'][$key]['order_id'] = (string)$value['order_id'];
            $orderData['items'][$key]['sub_order_id'] = (string)$value['id'];
            $orderData['items'][$key]['item_bn'] = (string)$value['item_bn'];
            $orderData['items'][$key]['total_fee'] = (string)$value['total_fee'];
            $orderData['items'][$key]['num'] = (string)$value['num'];
            $orderData['items'][$key]['price'] = (string)$value['price'];
            $orderData['items'][$key]['item_fee'] = (string)$value['item_fee'];
            $orderData['items'][$key]['discount_fee'] = (string)$value['discount_fee'];
            $orderData['items'][$key]['discount_info'] = is_array($value['discount_info']) ? json_encode($value['discount_info']) : $value['discount_info'];
            $orderData['items'][$key]['cost_fee'] = (string)$value['cost_fee'];
            $orderData['items'][$key]['item_spec_desc'] = (string)$value['item_spec_desc'];
            $orderData['items'][$key]['item_name'] = (string)$value['item_name'];
            $orderData['items'][$key]['item_pic'] = (string)$value['pic'];
            $orderData['items'][$key]['profit_amount'] = '0';
            $orderData['items'][$key]['order_status'] = '1';
            if ($value['order_item_type'] == 'gift') {
                $orderData['items'][$key]['item_type'] = '1';
            } else {
                $orderData['items'][$key]['item_type'] = '0';
            }
        }
        return $orderData;
    }

    private function getShopSalesperson($companyId, $orderId, $guideData, $isGetNewRelData = true)
    {
        //查看订单入参导购信息，导购id 和 导购工号
        $personIds = array_filter(array_column($guideData, 'person_id'));
        $shopsalesRepository = app('registry')->getManager('default')->getRepository(ShopSalesperson::class);
        $salesperson = $shopsalesRepository->getLists(['company_id' => $companyId, 'salesperson_id' => $personIds], ['salesperson_id', 'work_userid']);
        if (empty($salesperson)) {
            throw new \Exception($orderId.'未找到导购信息');
        }
        $salesperson = array_column($salesperson, 'work_userid', 'salesperson_id');

        //查看订单入参门店信息，门店id 和 门店编号
        $storeIds = array_filter(array_column($guideData, 'store_id'));
        $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $distributor = $distributorRepository->getLists(['company_id' => $companyId, 'distributor_id' => $storeIds], ['distributor_id', 'shop_code']);
        if (empty($distributor)) {
            throw new \Exception($orderId.'未找到门店信息');
        }
        $distributor = array_column($distributor, 'shop_code', 'distributor_id');

        //是否需要获取最新的导购门店关系，不获取则以订单入参数据为主
        if ($isGetNewRelData) {
            //获取导购绑定的门店信息。导购工号：门店编号
            $request = new Request();
            $params['work_userid'] = implode(',', array_values($salesperson));
            $store = $request->call($companyId, 'basics.salesperson.relShop', $params)['data'] ?? [];
            if (empty($store)) {
                throw new \Exception($orderId.'未找到最新导购关联门店信息');
            }
            $store = array_column($store, 'shop_code', 'work_userid');

            //检测订单入参门店编号与导购绑定门店是否一致;
            $update = [];
            foreach ($store as $workUserid => $shopCode) {
                if (!in_array($shopCode, $distributor)) { //导购绑定门店 不存在与订单入参门店集合
                    $update[] = $shopCode;
                }
            }
            if ($update) {
                $ndistributor = $distributorRepository->getLists(['company_id' => $companyId, 'shop_code' => $update], ['distributor_id', 'shop_code']);
                $ndistributor = array_column($ndistributor, 'distributor_id', 'shop_code');
            }
        }
        foreach ($guideData as $type => $data) {
            $workUserid = $salesperson[$data['person_id']] ?? '';
            if ($isGetNewRelData) {
                $shopCode = $store[$workUserid] ?? '';
            } else {
                $shopCode = $distributor[$data['store_id']] ?? '';
            }
            $guideData[$type]['new_store_id'] = 0;
            $guideData[$type]['person_code'] = $workUserid;
            $guideData[$type]['store_code'] = $shopCode;
            if ($ndistributor[$shopCode] ?? null) {
                $guideData[$type]['new_store_id'] = $ndistributor[$shopCode];
            }
        }
        return $guideData;
    }

    private function getTrade($companyId, $orderId)
    {
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $tradeInfo = $tradeRepository->getInfo(['company_id' => $companyId, 'order_id' => $orderId, 'trade_state' => ['REFUND', 'SUCCESS']]);
        if (empty($tradeInfo)) {
            return null;
        }
        $result['trade_id'] = (string)$tradeInfo['trade_id'];
        $result['order_id'] = (string)$tradeInfo['order_id'];
        $result['mobile'] = (string)$tradeInfo['mobile'];
        $result['mch_id'] = (string)$tradeInfo['mch_id'];
        $result['total_fee'] = (string)$tradeInfo['total_fee'];
        $result['discount_fee'] = (string)$tradeInfo['discount_fee'];
        $result['pay_fee'] = (string)$tradeInfo['pay_fee'];
        $result['order_source'] = '1';
        $result['discount_info'] = is_array($tradeInfo['discount_info']) ? json_encode($tradeInfo['discount_info']) : $tradeInfo['discount_info'];
        switch ($tradeInfo['pay_type']) {
            case 'wxpay':
                $pay_type = '1';
                break;
            case 'deposit':
                $pay_type = '2';
                break;
            case 'pos':
                $pay_type = '3';
                break;
            case 'point':
                $pay_type = '4';
                break;
            default:
                $pay_type = '1'; //默认现金支付
        }
        $result['pay_type'] = (string)$pay_type;
        $result['transaction_id'] = (string)$tradeInfo['transaction_id'];
        $result['pay_time'] = empty($tradeInfo['time_expire']) ? null : date('Y-m-d H:i:s', $tradeInfo['time_expire']);
        return $result;
    }

    private function getRefund($companyId, $orderId, &$aftersalesBns)
    {
        $refundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
        $tradeLists = $refundRepository->getList(['company_id' => $companyId, 'order_id' => $orderId, 'refund_status' => 'SUCCESS'])['list'] ?? null;
        if (empty($tradeLists)) {
            return null;
        }
        $result = [];
        foreach ($tradeLists as $value) {
            if (!empty($value['aftersales_bn'] ?? null)) {
                $aftersalesBns[] = $value['aftersales_bn'];
            }
            $result[] = [
                'refund_bn' => (string)$value['refund_bn'],
                'trade_id' => (string)$value['trade_id'],
                'aftersales_bn' => (string)$value['aftersales_bn'],
                'refund_fee' => (string)$value['refund_fee'],
                'refunded_fee' => (string)$value['refunded_fee'],
                'refund_success_time' => empty($value['refund_success_time']) ? null : date('Y-m-d H:i:s', $value['refund_success_time']),
            ];
        }
        return $result;
    }

    private function getAftersale($companyId, $orderId, $aftersalesBns)
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'aftersales_status' => 2,
        ];
        empty($aftersalesBns) ?: $filter['aftersales_bn'] = $aftersalesBns;
        $aftersalesRepository = app('registry')->getManager('default')->getRepository(Aftersales::class);
        $aftersalesList = $aftersalesRepository->getList($filter)['list'] ?? null;
        if (empty($aftersalesList)) {
            return null;
        }
        $result = [];
        foreach ($aftersalesList as $aftersalesInfo) {
            $data = [
                'aftersales_bn' => (string)$aftersalesInfo['aftersales_bn'],
                'order_id' => (string)$aftersalesInfo['order_id'],
                'aftersales_type' => (string)$aftersalesInfo['aftersales_type'],
                'refund_fee' => (string)$aftersalesInfo['refund_fee'],
                'reason' => (string)$aftersalesInfo['reason'],
                'description' => (string)$aftersalesInfo['description'],
                'refuse_reason' => (string)$aftersalesInfo['refuse_reason'],
                'apply_time' => date('Y-m-d H:i:s', $aftersalesInfo['create_time']),
                'evidence_pic' => is_array($aftersalesInfo['evidence_pic']) ? json_encode($aftersalesInfo['evidence_pic']) : $aftersalesInfo['evidence_pic'],
            ];
            if ($aftersalesInfo['aftersales_status'] > 0) {
                $data['aftersales_status'] = (string)($aftersalesInfo['aftersales_status'] - 1);
            } else {
                $data['aftersales_status'] = (string)$aftersalesInfo['aftersales_status'];
            }
            $result[$aftersalesInfo['aftersales_bn']] = $data;
        }

        $aftersalesDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class);
        $detailList = $aftersalesDetailRepository->getList($filter)['list'] ?? null;
        foreach ($detailList as $value) {
            $data = [
                'sub_order_id' => (string)$value['sub_order_id'],
                'item_bn' => (string)$value['item_bn'],
                'num' => (string)$value['num'],
                'refund_fee' => (string)$value['refund_fee'],
            ];
            $result[$value['aftersales_bn']]['items'][] = $data;
        }
        return array_values($result);
    }

    private function getOrderStatus($statusDes)
    {
        switch ($statusDes) {
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
        return $statusMsg;
    }
}
