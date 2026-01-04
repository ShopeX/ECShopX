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

namespace OrdersBundle\Services\Orders;

use OrdersBundle\Entities\SubOrders;
use OrdersBundle\Entities\ServiceOrders;
use OrdersBundle\Entities\OrderAssociations;
use OrdersBundle\Entities\Trade;

use OrdersBundle\Traits\GetOrderIdTrait;
use OrdersBundle\Traits\GetCartTypeServiceTrait;
use OrdersBundle\Traits\GetUserIdByMobileTrait;

use GoodsBundle\Services\ItemsService;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;

use Exception;

use DataCubeBundle\Services\SourcesService;
use OrdersBundle\Interfaces\OrderInterface;
use OrdersBundle\Traits\OrderSettingTrait;

class AbstractServiceOrder implements OrderInterface
{
    use OrderSettingTrait;
    use GetOrderIdTrait;
    use GetUserIdByMobileTrait;
    use GetCartTypeServiceTrait;

    public $serviceOrderRepository;
    public $orderAssociationsRepository;
    public $subOrdersRepository;

    public function __construct()
    {
        $this->serviceOrderRepository = app('registry')->getManager('default')->getRepository(ServiceOrders::class);
        $this->orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
        $this->subOrdersRepository = app('registry')->getManager('default')->getRepository(SubOrders::class);
    }

    public function create($orderData)
    {
        $ordersResult = $this->serviceOrderRepository->create($orderData);

        $this->orderAssociationsRepository->create($orderData);

        if ($orderData['type_labels']) {
            foreach ($orderData['type_labels'] as $label) {
                $subOrder = [
                    'order_id' => $orderData['order_id'],
                    'company_id' => $label['companyId'],
                    'label_id' => $label['labelId'],
                    'label_name' => $label['labelName'],
                    'label_price' => $label['labelPrice'],
                    'item_id' => $label['itemId'],
                    'item_name' => $orderData['title'],
                    'num' => $label['num'],
                    'is_not_limit_num' => $label['isNotLimitNum'],
                    'limit_time' => $label['limitTime'],
                ];
                $this->subOrdersRepository->create($subOrder);
            }
        }

        return $ordersResult;
    }

    /**
     * 服务类商品不需要减库存
     */
    public function minusItemStore()
    {
        return true;
    }

    //更新服务类订单
    public function update($filter, $updateInfo)
    {
        $order = $this->serviceOrderRepository->get($filter['company_id'], $filter['order_id']);
        if (!$order) {
            throw new Exception(trans('OrdersBundle/Order.order_not_found', ['id' => $filter['order_id']]));
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->serviceOrderRepository->update($filter, $updateInfo);
            $result = $this->orderAssociationsRepository->update($filter, $updateInfo);

            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 更新销量
     * @param $orderId 订单id
     */
    public function incrSales($orderId, $companyId)
    {
        $info = $this->serviceOrderRepository->get($companyId, $orderId);
        $itemsService = new ItemsService();
        $itemsService->incrSales($info->getItemId(), $info->getItemNum());
        return true;
    }

    /**
     * 统计服务类订单数量
     */
    public function countOrderNum($filter)
    {
        return $this->serviceOrderRepository->count($filter);
    }

    /**
     * 获取订单列表
     */
    public function getOrderList($filter, $page = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'])
    {
        if (isset($filter['order_type'])) {
            unset($filter['order_type']);
        }

        $filter = $this->checkMobile($filter);
        $offset = ($page - 1) * $limit;
        $result['list'] = $this->serviceOrderRepository->getList($filter, $offset, $limit, $orderBy);

        if ($result['list']) {
            $sourceIds = array_column($result['list'], 'source_id');
            $objSource = new SourcesService();
            $sourceInfo = $objSource->getSourcesList(['company_id' => $filter['company_id'], 'source_id' => $sourceIds], 1, 100);
            $sourceList = [];
            if ($sourceInfo['list']) {
                $sourceList = array_bind_key($sourceInfo['list'], 'sourceId');
            }
            foreach ($result['list'] as $k => $v) {
                $result['list'][$k]['source_name'] = '-';
                if ($sourceList && $v['source_id'] > 0) {
                    $result['list'][$k]['source_name'] = $sourceList[$v['source_id']]['sourceName'];
                }
                $result['list'][$k]['create_date'] = date('Y-m-d H:i:s', $v['create_time']);
            }
        }
        $result['pager']['count'] = $this->serviceOrderRepository->count($filter);
        $result['pager']['page_no'] = $page;
        $result['pager']['page_size'] = $limit;

        return $result;
    }

    /**
     * 获取订单详情
     */
    public function getOrderInfo($companyId, $orderId, $checkaftersales = false, $from = 'api')
    {
        $order = $this->serviceOrderRepository->get($companyId, $orderId);
        if (!$order) {
            throw new Exception(trans('OrdersBundle/Order.order_not_found', ['id' => $orderId]));
        }
        //获取交易单信息
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $trade = $tradeRepository->getTradeList($filter);
        if ($trade['list']) {
            $tradeInfo = $trade['list'][0];
        }
        $orderInfo = [
            'order_id' => $order->getOrderId(),
            'title' => $order->getTitle(),
            'company_id' => $order->getCompanyId(),
            'shop_id' => $order->getShopId(),
            'store_name' => $order->getStoreName(),
            'user_id' => $order->getUserId(),
            'consume_type' => $order->getConsumeType(),
            'item_id' => $order->getItemId(),
            'item_num' => $order->getItemNum(),
            'mobile' => $order->getMobile(),
            'total_fee' => $order->getTotalFee(),
            'order_status' => $order->getOrderStatus(),
            'order_source' => $order->getOrderSource(),
            'operator_desc' => $order->getOperatorDesc(),
            'order_type' => $order->getOrderType(),
            'create_time' => $order->getCreateTime(),
            'update_time' => $order->getUpdateTime(),
            'auto_cancel_time' => $order->getAutoCancelTime(),
            'date_type' => $order->getDateType(),
            'begin_date' => $order->getBeginDate(),
            'end_date' => $order->getEndDate(),
            'fixed_term' => $order->getFixedTerm(),
            'item_fee' => $order->getItemFee() ?: $order->getTotalFee(),
            'cost_fee' => $order->getCostFee(),
            'member_discount' => $order->getMemberDiscount(),
            'coupon_discount' => $order->getCouponDiscount(),
            'member_discount_desc' => $order->getMemberDiscountDesc(),
            'coupon_discount_desc' => $order->getMemberDiscountDesc(),
            'freight_fee' => 0,
            'fee_type' => $order->getFeeType(),
            'fee_rate' => $order->getFeeRate(),
            'fee_symbol' => $order->getFeeSymbol(),
        ];

        $result = [
            'orderInfo' => $orderInfo,
            'tradeInfo' => isset($tradeInfo) ? $tradeInfo : [],
        ];

        return $result;
    }

    // 订单支付状态修改操作
    public function orderStatusUpdate($filter, $orderStatus, $payType = '')
    {
        $serviceUpdate = ['order_status' => $orderStatus];
        if ($payType) {
            $serviceUpdate['pay_type'] = $payType;
        }
        $this->serviceOrderRepository->update($filter, $serviceUpdate);
        $result = $this->orderAssociationsRepository->update($filter, ['order_status' => $orderStatus]);

        if ($orderStatus == 'DONE') {
            $this->addNewRights($result['company_id'], $result['user_id'], $result['order_id']);
        }

        return $result;
    }

    /**
     * 对订单赠送权益
     */
    public function addNewRights($companyId, $userId, $orderId)
    {
        $orderdetail = $this->getOrderInfo($companyId, $orderId);
        $orders = $orderdetail['orderInfo'];
        $subOrders = $this->subOrdersRepository->list(['order_id' => $orderId, 'company_id' => $companyId], ['label_id' => 'DESC'], 100, 1);
        $rightsObj = new RightsService(new TimesCardService());
        if ($orders['consume_type'] == 'all') {
            if ($orders['date_type'] == 'DATE_TYPE_FIX_TIME_RANGE') {
                $start_time = $orders['begin_date'];
                $end_time = $orders['end_date'];
            }
            if ($orders['date_type'] == 'DATE_TYPE_FIX_TERM') {
                $start_time = strtotime(date('Y-m-d 00:00:00', time()));
                $end_time = strtotime(date('Y-m-d 23:59:59', $start_time + 86400 * $orders['fixed_term']));
            }
            $label_infos = [];
            foreach ($subOrders['list'] as $v) {
                $label_infos[] = ['label_id' => $v['label_id'], 'label_name' => $v['label_name']];
            }
            $data = [
                'user_id' => $userId,
                'company_id' => $orders['company_id'],
                'rights_name' => $orders['title'],
                'rights_subname' => '',
                'total_num' => $orders['item_num'],
                'total_consum_num' => 0,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'order_id' => $orders['order_id'],
                'can_reservation' => false,
                'label_infos' => $label_infos,
                'rights_from' => ($orders['order_source'] == "shop") ? "代客下单获取" : '购买获取',
                'mobile' => $orders['mobile'],
                'is_not_limit_num' => 2,
            ];
            $rightsObj->addRights($companyId, $data);
        } elseif ($orders['consume_type'] == 'every') {
            foreach ($subOrders['list'] as $v) {
                $start_time = strtotime(date('Y-m-d 00:00:00', time()));
                $end_time = strtotime(date('Y-m-d 23:59:59', $start_time + 86400 * $v['limit_time']));

                $data = [
                    'user_id' => $userId,
                    'company_id' => $v['company_id'],
                    'rights_name' => $v['item_name'],
                    'rights_subname' => $v['label_name'],
                    'total_num' => $v['num'],
                    'total_consum_num' => 0,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'order_id' => $orders['order_id'],
                    'can_reservation' => true,
                    'label_infos' => [['label_id' => $v['label_id'], 'label_name' => $v['label_name']]],
                    'rights_from' => ($orders['order_source'] == "shop") ? "代客下单获取" : '购买获取',
                    'operator_desc' => $orders['operator_desc'],
                    'mobile' => $orders['mobile'],
                    'is_not_limit_num' => $v['is_not_limit_num'],
                ];
                $rightsObj->addRights($companyId, $data);
            }
        }
    }

    public function delivery($params)
    {
        return ;
    }
}
