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

namespace EmployeePurchaseBundle\Services;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\Orders\AbstractNormalOrder;
use EmployeePurchaseBundle\Services\CartService;
use EmployeePurchaseBundle\Services\ActivitiesService;
use EmployeePurchaseBundle\Services\EmployeesService;
use EmployeePurchaseBundle\Services\RelativesService;
use EmployeePurchaseBundle\Services\ActivityItemsService;
use EmployeePurchaseBundle\Services\MemberActivityAggregateService;
use EmployeePurchaseBundle\Services\MemberActivityItemsAggregateService;
use EmployeePurchaseBundle\Services\OrdersRelActivityService;
use OrdersBundle\Traits\GetUserIdByMobileTrait;
use OrdersBundle\Services\ShippingTemplatesService;
use OrdersBundle\Services\TradeSetting\CancelService;
use OrdersBundle\Services\TradeSettingService;
use DistributionBundle\Services\DistributorService;

class NormalOrderService extends AbstractNormalOrder
{
    use GetUserIdByMobileTrait;

    public $orderClass = 'employee_purchase';

    public $orderType = 'normal';

    // 订单是否支持优惠券优惠
    public $isSupportCouponDiscount = false;

    // 订单是否需要进行门店验证
    public $isCheckShopValid = false;

    // 订单是否需要进行店铺验证
    public $isCheckDistributorValid = true;

    // 需要支持购物车
    public $isSupportCart = true;

    //订单是否支持积分抵扣
    public $isSupportPointDiscount = false;

    // 订单是否支持获取积分
    public $isSupportGetPoint = false;

    private $activityInfo = [];
    private $orderRelData = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function checkCreateOrderNeedParams($params, $isCreate)
    {
        $rules = [
            'company_id'       => ['required', '公司ID必填'],
            'enterprise_id'    => ['required', '企业ID必填'],
            'activity_id'      => ['required', '活动ID必填'],
            'user_id'          => ['required', '用户ID必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
    }

    public function checkoutCartItems($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'enterprise_id' => $params['enterprise_id'],
            'activity_id' => $params['activity_id'],
            'user_id' => $params['user_id'],
            'cart_type' => $params['cart_type'] ?? 'cart',
        ];
        $cartService = new CartService();
        $cartData = $cartService->getCartdataList($filter, true);
        $cartlist = reset($cartData['valid_cart']);
        if (!$cartlist) {
            throw new ResourceException('购物车为空');
        }

        $params['items'] = [];
        foreach ($cartlist['list'] as $cart) {
            if ($cart['is_checked']) {
                $params['items'][] = [   //订单中的商品数据
                    'item_id' => $cart['item_id'],
                    'price' => $cart['price'],
                    'num' => $cart['num'],
                ];
            }
        }

        return $params;
    }

    /**
     * 活动及限购检查
     */
    public function check($params)
    {
        $activitiesService = new ActivitiesService;
        $activity = $activitiesService->getInfo(['company_id' => $params['company_id'], 'id' => $params['activity_id']]);

        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if ($activity['status'] == 'cancel') {
            throw new ResourceException('活动已取消');
        }

        if ($activity['status'] == 'pending') {
            throw new ResourceException('活动已暂停');
        }

        if ($activity['status'] == 'over') {
            throw new ResourceException('活动已暂停');
        }

        if (!in_array($params['enterprise_id'], $activity['enterprise_id'])) {
            throw new ResourceException('企业不参与该活动');
        }

        $employeesService = new EmployeesService();
        $employee = $employeesService->check($params['company_id'], $params['enterprise_id'], $params['user_id']);
        if ($employee) {
            if ($activity['employee_begin_time'] > time() || $activity['employee_end_time'] < time()) {
                throw new ResourceException('非员工购买时段');
            }
        } else {
            if ($activity['if_relative_join']) {
                $relativesService = new RelativesService();
                $relative = $relativesService->check($params['company_id'], $params['enterprise_id'], $params['activity_id'], $params['user_id']);
                if ($relative) {
                    if ($activity['relative_begin_time'] > time() || $activity['relative_end_time'] < time()) {
                        throw new ResourceException('非亲友购买时段');
                    }
                } else {
                    throw new ResourceException('既不是员工也不是亲友，无权购买');
                }
            } else {
                throw new ResourceException('不是员工，无权购买');
            }
        }

        $this->activityInfo = $activity;

        return true;
    }

    public function formatOrderData($orderData, $params, $isCheck)
    {
        $itemIds = array_column($orderData['items'], 'item_id');

        $activityItemsService = new ActivityItemsService();
        $activityItemList = $activityItemsService->getLists(['company_id' => $params['company_id'], 'activity_id' => $params['activity_id'], 'item_id' => $itemIds]);
        $activityItemList = array_column($activityItemList, null, 'item_id');

        $memberActivityItemsAggregateService = new MemberActivityItemsAggregateService();
        $memberActivityItemsAggregateList = $memberActivityItemsAggregateService->getLists(['company_id' => $params['company_id'], 'enterprise_id' => $params['enterprise_id'], 'user_id' => $params['user_id'], 'activity_id' => $params['activity_id'], 'item_id' => $itemIds]);
        $memberActivityItemsAggregateList = array_column($memberActivityItemsAggregateList, null, 'item_id');
        foreach ($orderData['items'] as $item) {
            if ($activityItemList[$item['item_id']]['limit_num'] > 0) {
                $itemAggregateNum = 0;
                if (isset($memberActivityItemsAggregateList[$item['item_id']])) {
                    $itemAggregateNum = $memberActivityItemsAggregateList[$item['item_id']]['aggregate_num'];
                }
                if ($itemAggregateNum + $item['num'] > $activityItemList[$item['item_id']]['limit_num']) {
                    if ($isCheck) {
                        throw new ResourceException($item['item_name'].'超过限购数量');
                    } else {
                        $orderData['extraTips'] = $item['item_name'].'超过限购数量';
                    }
                }
            }

            if ($activityItemList[$item['item_id']]['limit_fee'] > 0) {
                $itemAggregateFee = 0;
                if (isset($memberActivityItemsAggregateList[$item['item_id']])) {
                    $itemAggregateFee = $memberActivityItemsAggregateList[$item['item_id']]['aggregate_fee'];
                }
                if ($itemAggregateFee + $item['item_fee'] > $activityItemList[$item['item_id']]['limit_fee']) {
                    if ($isCheck) {
                        throw new ResourceException($item['item_name'].'超过限额');
                    } else {
                        $orderData['extraTips'] = $item['item_name'].'超过限额';
                    }
                }
            }
        }

        $memberActivityAggregateService = new MemberActivityAggregateService();
        $aggregateFee = $memberActivityAggregateService->getAggregateFee($params['company_id'], $params['enterprise_id'], $params['activity_id'], $params['user_id']);
        if ($aggregateFee['left_fee'] < $orderData['item_fee']) {
            if ($isCheck) {
                throw new ResourceException('超过活动限额');
            } else {
                $orderData['extraTips'] = '超过活动限额';
            }
        }

        if ($this->activityInfo['minimum_amount'] > 0 && $orderData['item_fee'] < $this->activityInfo['minimum_amount']) {
            if ($isCheck) {
                throw new ResourceException('订单商品定额不能少于'.bcdiv($this->activityInfo['minimum_amount'], 100, 2).'元');
            } else {
                $orderData['extraTips'] = '订单商品定额不能少于'.bcdiv($this->activityInfo['minimum_amount'], 100, 2).'元';
            }
        }

        $orderData['act_id'] = $params['activity_id'];

        foreach ($orderData['items'] as $k => $row) {
            $orderData['items'][$k]['act_id'] = $params['activity_id'];
        }

        return $orderData;
    }

    public function createExtend($orderData, $params)
    {
        $endTime = max($this->activityInfo['employee_end_time'], $this->activityInfo['relative_end_time']);
        $relData = [
            'order_id'       => $orderData['order_id'],
            'company_id'     => $orderData['company_id'],
            'enterprise_id'  => $params['enterprise_id'],
            'activity_id'    => $params['activity_id'],
            'user_id'        => $params['user_id'],
            'if_share_store' => $this->activityInfo['if_share_store'],
            'close_modify_time' => $endTime + $this->activityInfo['close_modify_hours_after_activity'] * 3600,
        ];
        $relService = new OrdersRelActivityService();
        $result = $relService->create($relData);
        $this->orderRelData = $result;
        return $result;
    }

    public function minusItemStore($orderData)
    {
        if (!$this->activityInfo['if_share_store']) {
            $activityItemsService = new ActivityItemsService();
            foreach ($orderData['items'] as $item) {
                $activityItemsService->minusActivityItemStore($orderData['company_id'], $this->activityInfo['id'], $item['item_id'], $item['num']);
            }
        } else {
            parent::minusItemStore($orderData);
        }

        // 更新额度
        $memberActivityAggregateService = new MemberActivityAggregateService();
        $memberActivityAggregateService->addAggregateFee($orderData['company_id'], $this->orderRelData['enterprise_id'], $this->activityInfo['id'], $orderData['user_id'], $orderData['item_fee']);

        // 更新商品限购
        $memberActivityItemsAggregateService = new MemberActivityItemsAggregateService();
        foreach ($orderData['items'] as $item) {
            $memberActivityItemsAggregateService->addItemAggregate($orderData['company_id'], $this->orderRelData['enterprise_id'], $this->activityInfo['id'], $orderData['user_id'], $item['item_id'], $item['item_fee'], $item['num']);
        }
    }

    public function emptyCart($params)
    {
        $cartService = new CartService();
        $cartType = $params['cart_type'] ?? 'cart';
        if ($cartType == 'fastbuy') {
            $cartService->setFastBuyCart($params['company_id'], $params['enterprise_id'], $params['activity_id'], $params['user_id'], []);
        } else {
            $filter = [
                'company_id' => $params['company_id'],
                'enterprise_id' => $params['enterprise_id'],
                'activity_id' => $params['activity_id'],
                'user_id' => $params['user_id'],
                'item_id' => array_column($params['items'], 'item_id'),
            ];
            $cartService->deleteBy($filter);
        }
    }

    public function getOrderList($filter, $page = 0, $limit = -1, $orderBy = ['create_time' => 'DESC'], $isGetTotal = true, $from = 'api')
    {
        $filter['order_type'] = 'normal';
        $filter['order_class'] = 'employee_purchase';
        $filter = $this->checkMobile($filter);

        $offset = ($page - 1) * $limit;
        $relService = new OrdersRelActivityService();
        $result = $relService->getOrderListWithActivity($filter, $offset, $limit, $orderBy);

        if ($result['list']) {
            $membersDelete = $this->membersDeleteRecordRepository->getLists(['company_id' => $filter['company_id'], 'user_id' => array_column($result['list'], 'user_id')], 'user_id');
            if (!empty($membersDelete)) {
                $deleteUsers = array_column($membersDelete, 'user_id');
            }
            
            // 获取sale_salesman_distributor_id对应的店铺信息
            $distributorService = new DistributorService();
            $saleSalesmanDistributorIds = array_filter(array_unique(array_column($result['list'], 'sale_salesman_distributor_id')), function ($distributorId) {
                return is_numeric($distributorId) && $distributorId >= 0;
            });
            // 转换为整数确保类型一致
            $saleSalesmanDistributorIds = array_map('intval', $saleSalesmanDistributorIds);
            $saleSalesmanDistributorIds = array_values(array_unique($saleSalesmanDistributorIds));
            $storeData = [];
            // 检查是否需要总店信息（0）
            $needZeroStore = in_array(0, $saleSalesmanDistributorIds, true);
            // 如果包含0，先从列表中移除0，因为0（总店）需要单独获取
            $queryDistributorIds = array_filter($saleSalesmanDistributorIds, function($id) {
                return $id !== 0;
            });
            if ($queryDistributorIds) {
                // 一次性查询所有店铺信息（不包括0，因为0需要单独获取）
                $storeList = $distributorService->getDistributorOriginalList([
                    'company_id' => $filter['company_id'],
                    'distributor_id' => $queryDistributorIds,
                ], 1, $limit);
                if (!empty($storeList['list'])) {
                    $storeData = array_column($storeList['list'], null, 'distributor_id');
                    // 将键转换为整数，确保类型一致
                    $storeData = array_combine(array_map('intval', array_keys($storeData)), array_values($storeData));
                }
            }
            // 如果需要总店信息（0），则附加总店信息
            if ($needZeroStore) {
                $storeData[0] = $distributorService->getDistributorSelfSimpleInfo($filter['company_id']);
            }
            
            $service = new TradeSettingService(new CancelService());
            $setting = $service->getSetting($filter['company_id']);
            foreach ($result['list'] as $k => $v) {
                $result['list'][$k]['can_apply_cancel'] = 0;
                if ($v['order_status'] == 'NOTPAY' || $v['order_status'] == 'PAYED') {
                    $result['list'][$k]['can_apply_cancel'] = 1;
                }
                if ($v['cancel_status'] != 'NO_APPLY_CANCEL') {
                    if (!($setting['repeat_cancel'] ?? false)) {
                        $result['list'][$k]['can_apply_cancel'] = 0;
                    }

                    if ($v['cancel_status'] != 'FAILS') {
                        $result['list'][$k]['can_apply_cancel'] = 0;
                    }
                }

                if ($v['order_status'] == 'NOTPAY' && $v['auto_cancel_time'] - time() <= 0 && $v['order_class'] != 'drug') {
                    $v['order_status'] = 'CANCEL';
                    $result['list'][$k]['order_status'] = 'CANCEL';
                }

                $result['list'][$k]['order_status_msg'] = $this->getOrderStatusMsg($v, null, $from);
                $result['list'][$k]['order_status_des'] = $v['order_status_des'];
                // 店务app附加数据
                $result['list'][$k]['app_info'] = $v['app_info'] ?? [];

                // 附加销售员店铺信息（包括0对应的总店信息）
                $saleSalesmanDistributorId = (int)$v['sale_salesman_distributor_id'];
                $result['list'][$k]['sale_salesman_distributor_info'] = $storeData[$saleSalesmanDistributorId] ?? [];

                $result['list'][$k]['create_date'] = date('Y-m-d H:i:s', $v['create_time']);

                $result['list'][$k]['items'] = $this->normalOrdersItemsRepository->get($v['company_id'], $v['order_id']);

                //发货单新旧兼容, 部分发货的订单需继续按照原发货流程进行
                $result['list'][$k]['delivery_type'] = 'new';
                if (!empty($v['delivery_code'])) {
                    $result['list'][$k]['delivery_type'] = 'old';
                } else {
                    foreach ($result['list'][$k]['items'] as $items_val) {
                        if (!empty($items_val['delivery_code'])) {
                            $result['list'][$k]['delivery_type'] = 'old';
                            break;
                        }
                    }
                }

                //判断发货单是否整单发货，适用新发货单的模式
                if ($result['list'][$k]['delivery_type'] == 'new') {
                    $_filter = [
                        'order_id' => $v['order_id']
                    ];
                    $orders_delivery_info = $this->ordersDeliveryRepository->getInfo($_filter);
                    if (!empty($orders_delivery_info)) {
                        $result['list'][$k]['orders_delivery_id'] = $orders_delivery_info['orders_delivery_id'];
                        $result['list'][$k]['is_all_delivery'] = $orders_delivery_info['package_type'] == 'batch' ? true : false;
                        $result['list'][$k]['delivery_corp'] = $orders_delivery_info['delivery_corp'];
                        $result['list'][$k]['delivery_corp_name'] = $orders_delivery_info['delivery_corp_name'];
                        $result['list'][$k]['delivery_code'] = $orders_delivery_info['delivery_code'];
                    } else {
                        $result['list'][$k]['orders_delivery_id'] = '';
                        $result['list'][$k]['is_all_delivery'] = '';
                        $result['list'][$k]['delivery_corp'] = '';
                        $result['list'][$k]['delivery_corp_name'] = '';
                        $result['list'][$k]['delivery_code'] = '';
                    }
                }

                $result['list'][$k]['user_delete'] = false;
                if (!empty($deleteUsers)) {
                    if (in_array($v['user_id'], $deleteUsers)) {
                        $result['list'][$k]['user_delete'] = true;
                    }
                }
                if ((!$v['order_auto_close_aftersales_time'] || $v['order_auto_close_aftersales_time'] > time()) && $v['left_aftersales_num'] > 0) {
                    $result['list'][$k]['can_apply_aftersales'] = 1;
                }
                if($v['order_status'] == 'CANCEL') {
                    $result['list'][$k]['can_apply_aftersales'] = 0;
                }
            }
        }

        if ($isGetTotal) {
            $result['pager']['count'] = $result['total_count'];
        }
        $result['pager']['page_no'] = intval($page);
        $result['pager']['page_size'] = intval($limit);

        return $result;
    }

    public function getOrderInfo($companyId, $orderId, $checkaftersales = false, $from = 'api')
    {
        $result = parent::getOrderInfo($companyId, $orderId, $checkaftersales, $from);

        $ordersRelActivityService = new OrdersRelActivityService();
        $relData = $ordersRelActivityService->getInfo(['company_id' => $companyId, 'order_id' => $orderId]);
        $result['orderInfo'] = array_merge($result['orderInfo'], $relData);

        return $result;
    }

    public function cancelOrder($params)
    {
        $result = parent::cancelOrder($params);

        if ($result['refund_status'] == 'SUCCESS') {
            $orderData = $this->getOrderInfo($params['company_id'], $params['order_id']);
            $this->restoreItemStoreAndAggregate($orderData['orderInfo']);
        }

        return $result;
    }

//    public function confirmCancelOrder($params)
//    {
//        $result = parent::confirmCancelOrder($params);
//
//        if ($result['refund_status'] == 'AUDIT_SUCCESS') {
//            $orderData = $this->getOrderInfo($params['company_id'], $params['order_id']);
//            $this->restoreItemStoreAndAggregate($orderData['orderInfo']);
//        }
//
//        return $result;
//    }

    public function restoreItemStoreAndAggregate($orderData)
    {
        if (!$orderData['if_share_store']) {
            $activityItemsService = new ActivityItemsService();
            foreach ($orderData['items'] as $item) {
                $activityItemsService->addActivityItemStore($orderData['company_id'], $orderData['act_id'], $item['item_id'], $item['num']);
            }
        }

        // 返还额度
        $memberActivityAggregateService = new MemberActivityAggregateService();
        $memberActivityAggregateService->minusAggregateFee($orderData['company_id'], $orderData['enterprise_id'], $orderData['act_id'], $orderData['user_id'], $orderData['item_fee']);

        // 返还商品限购
        $memberActivityItemsAggregateService = new MemberActivityItemsAggregateService();
        foreach ($orderData['items'] as $item) {
            $memberActivityItemsAggregateService->minusItemAggregate($orderData['company_id'], $orderData['enterprise_id'], $orderData['act_id'], $orderData['user_id'], $item['item_id'], $item['item_fee'], $item['num']);
        }
    }

    /**
     * 未发货的订单，在活动设置的可修改时间内，更新收货人信息
     * @param  array $params
     */
    public function updateOrderReceiver($params)
    {
        $orderData = $this->getOrderInfo($params['company_id'], $params['order_id']);
        $orderInfo = $orderData['orderInfo'];

        if ($orderInfo['user_id'] != $params['user_id']) {
            throw new ResourceException('只能修改自己的订单');
        }

        if ($orderInfo['order_class'] != 'employee_purchase') {
            throw new ResourceException('只能修改内购订单的收货地址');
        }

        if ($orderInfo['receipt_type'] != 'logistics') {
            throw new ResourceException('非快递配送订单不能修改收货地址');
        }

        if ($orderInfo['close_modify_time'] < time()) {
            throw new ResourceException('已超过可修改时间');
        }

        $shippingTemplatesService = new ShippingTemplatesService();
        $freightFee = $shippingTemplatesService->countFreightFee($orderInfo['items'], $params['company_id'], [$params['receiver_state'], $params['receiver_city'], $params['receiver_district']], true);
        if ($freightFee != $orderInfo['freight_fee']) {
            throw new ResourceException('新地址的运费和原订单不一致，不能修改');
        }

        // 更新订单的收货人信息
        $updateData = [
            'receiver_name' => $params['receiver_name'],
            'receiver_mobile' => $params['receiver_mobile'],
            'receiver_zip' => $params['receiver_zip'],
            'receiver_state' => $params['receiver_state'],
            'receiver_city' => $params['receiver_city'],
            'receiver_district' => $params['receiver_district'],
            'receiver_address' => $params['receiver_address'],
        ];
        return $this->normalOrdersRepository->updateOneBy(['company_id' => $params['company_id'], 'order_id' => $params['order_id']], $updateData);
    }
}
