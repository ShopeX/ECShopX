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

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Entities\CancelOrders;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\CartService;
use AftersalesBundle\Services\AftersalesRefundService;
use PointsmallBundle\Services\ItemsService;
use PointsmallBundle\Services\SettingService;
use PointBundle\Services\PointMemberService;
use PointsmallBundle\Services\ItemStoreService as PointsmallItemStoreService;

class PointsmallNormalOrderService extends AbstractNormalOrder
{
    //use GetAllItemAcitivityData;
    // 订单种类
    public $orderClass = 'pointsmall';

    // 订单类型 实体类订单 服务类订单 等其他订单
    public $orderType = 'normal';

    // 订单是否需要进行门店验证
    public $isCheckShopValid = false;

    // 积分兑换
    public $isCheckPoint = false;

    // 订单是否需要进行店铺验证
    public $isCheckDistributorValid = false;

    public $isSupportCart = true;

    // 订单是否需要验证白名单
    public $isCheckWhitelistValid = true;

    //订单是否支持积分抵扣
    public $isSupportPointDiscount = false;

    // 订单是否支持获取积分
    public $isSupportGetPoint = false;

    // 是否需要获取商品数据
    public $getSkuItems = true;

    public $TotalFee = [];

    public $TotalDiscountFee = [];

    public $orderItemPrcie = [];

    public $orderItemPoint = [];

    /**
     * 检查购物车商品数据
     * @param $params
     * @return mixed
     */
    public function checkoutCartItems($params)
    {
        if (isset($params['items']) && $params['items']) {
            foreach ($params['items'] as $key => $items) {
                $this->orderItemPoint[$items['item_id']] = $items['item_point'];
            }
            return $params;
        }

        $cartService = new CartService();
        $cartlist = $cartService->getCartList($params['company_id'], $params['user_id'], 0, $params['cart_type'], 'pointsmall', true)['valid_cart'];
        $cartlist = reset($cartlist);
        if (!$cartlist) {
            throw new ResourceException('购物车为空');
        }
        $params['items'] = [];
        foreach ($cartlist['list'] as $cart) {
            if ($cart['is_checked'] && $cart['num'] > 0) {
                $params['items'][] = [
                    'item_id' => $cart['item_id'],
                    'num' => $cart['num'],
                    'is_medicine' => $cart['is_medicine'] ?? 0,
                    'is_prescription' => $cart['is_prescription'] ?? 0,
                ];
                $this->orderItemPoint[$cart['item_id']] = $cart['point'];
            }
        }
        return $params;
    }

    /**
     * 获取sku的积分价格
     * @param $itemId
     * @return mixed
     */
    public function getOrderItemPoint($itemId)
    {
        return $this->orderItemPoint[$itemId];
    }


    /**
     * 根据条件获取积分商城的商品sku数据
     * @param $filter
     * @param int $page
     * @param int $pageSize
     * @return mixed
     */
    public function getSkuItems($filter, $page = 1, $pageSize = 2000)
    {
        $itemsService = new ItemsService();
        $itemList = $itemsService->getSkuItemsList($filter, 1, 100);
        return $itemList;
    }

    /**
     * 根据设置转换运费，并计算total_fee total_fee为现金支付的总金额
     * @param $company_id 企业ID
     * @param $total_fee 现金总金额
     * @param $freight_fee 运费金额
     * @param $point 订单积分支付的总数
     * @return int
     */
    public function getOrderTotalFee($company_id, $total_fee, $freight_fee, $point)
    {
        $settingService = new SettingService($company_id);
        $data = $settingService->moneyToPoint($freight_fee);
        $data['total_fee'] = $total_fee;
        $data['freight_fee'] = $data['money'];
        $data['point'] = $point;
        if ($data['freight_type'] == 'point') {
            $data['point'] = bcadd($point, $data['freight_fee'], 0);
        } else {
            $data['total_fee'] = bcadd($data['total_fee'], $data['freight_fee'], 0);
        }
        return $data;
    }

    /**
     * 取消订单，如果是未支付订单，执行退还积分
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function cancelOrder($data)
    {
        // 获取订单信息
        $order = $this->getInfo(['company_id' => $data['company_id'], 'order_id' => $data['order_id'], 'user_id' => $data['user_id']]);
        $order_status = $order['order_status'];
        $result = parent::cancelOrder($data);
        if (!$result) {
            return false;
        }
        if ($order_status == 'NOTPAY') {
            // 退还积分
            $this->backPoint($order);
        } else {
            if ($order['pay_type'] == 'point') {
                return $result;
            }
            return $result;
            // 生成退款单，不实际退款
           /* $aftersalesRefundService = new AftersalesRefundService();
            $tradeService = new TradeService();
            $trade = $tradeService->getInfo(['company_id' => $data['company_id'], 'order_id' => $data['order_id'], 'trade_state' => 'SUCCESS', 'pay_type' => 'point']);
            $refundData = [
                'company_id' => $order['company_id'],
                'user_id' => $order['user_id'],
                'order_id' => $order['order_id'],
                'trade_id' => $trade['trade_id'],
                'shop_id' => $order['shop_id'] ?? 0,
                'distributor_id' => $order['distributor_id'] ?? 0,
                'refund_type' => 1, // 1:取消订单退款,
                'refund_channel' => 'original', // 默认取消订单原路返回
                'refund_status' => 'READY', // 售前取消订单退款默认审核成功
                'refund_fee' => $trade['total_fee'],
                'refund_point' => $order['point'],
                'return_freight' => 1, // 1:退运费,
                'pay_type' => 'point', // 退款支付方式
                'currency' => ($trade['pay_type'] == 'point') ? '' : $trade['fee_type'],
                'cur_fee_type' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_type'],
                'cur_fee_rate' => $trade['cur_fee_rate'],
                'cur_fee_symbol' => ($trade['pay_type'] == 'point') ? '' : $trade['cur_fee_symbol'],
                'cur_pay_fee' => ($trade['pay_type'] == 'point') ? $order['point'] : $trade['cur_pay_fee'], // trade表没有单独积分字段，所以这样写
            ];
            $aftersalesRefundService->createRefund($refundData);
        */
        }
        return $result;
    }

    public function scheduleCancelOrders()
    {
        // 取消订单，每分钟执行一次，当前只处理一分钟内的订单
        $pageSize = 20;
        $time = time() + 60;
        $filter = [
            'auto_cancel_time|lt' => $time,
            'order_status' => 'NOTPAY',
            'order_class' => ['pointsmall'],
        ];
        $totalCount = $this->normalOrdersRepository->count($filter);
        $totalPage = ceil($totalCount / $pageSize);

        $pointsmallItemStoreService = new PointsmallItemStoreService();
        for ($i = 1; $i <= $totalPage; $i++) {
            $result = $this->normalOrdersRepository->getList($filter, 0, $pageSize);
            $orderIds = array_column($result, 'order_id');
            if ($orderIds) {
                $this->normalOrdersRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => 'CANCEL']);
                $this->orderAssociationsRepository->batchUpdateBy(['order_id|in' => $orderIds], ['order_status' => 'CANCEL']);

                $orderItems = $this->normalOrdersItemsRepository->getList(['order_id|in' => $orderIds]);


                foreach ($result as $order) {
                    // 退还积分支付的积分
                    $this->backPoint($order);

                    $orderProcessLog = [
                        'order_id' => $order['order_id'],
                        'company_id' => $order['company_id'],
                        'operator_type' => 'system',
                        'operator_id' => 0,
                        'remarks' => '订单取消',
                        'detail' => '订单单号：' . $order['order_id'] . '，取消订单退款',
                    ];
                    event(new OrderProcessLogEvent($orderProcessLog));
                }

                // 库存
                foreach ($orderItems['list'] as $row) {
                    $pointsmallItemStoreService->minusItemStore($row['item_id'], -$row['num'], true);
                }
            }
        }
    }

    // 已支付订单的取消订单并退款审核
    public function confirmCancelOrder($params)
    {
        if (!isset($params['refund_bn']) || !$params['refund_bn']) { // 没有传退款单
            $refundFilter = [
                'company_id' => $params['company_id'],
                'order_id' => $params['order_id'],
                'refund_type' => 1,
                'refund_status' => ['READY', 'AUDIT_SUCCESS', 'SUCCESS', 'CANCEL', 'REFUNDCLOSE', 'PROCESSING', 'CHANGE'],
            ];
        } else { // 传了退款单
            $refundFilter = [
                'company_id' => $params['company_id'],
                'refund_bn' => $params['refund_bn'],
                'order_id' => $params['order_id'],
            ];
        }
        $aftersalesRefundService = new AftersalesRefundService();

        $refundList = $aftersalesRefundService->getRefundsList($refundFilter);
        if (!$refundList['list']) {
            throw new ResourceException('没有查到退款单，无法同意取消订单');
        }
        foreach ($refundList['list'] as $key => $refund) {
            $result = $this->__confirmCancelOrder($params, $refundFilter, $refund);
        }
        return $result;
    }

    public function __confirmCancelOrder($params, $refundFilter, $refund)
    {
        $aftersalesRefundService = new AftersalesRefundService();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 同意退款
            if ($params['check_cancel'] == '1') {
                // 生成退款单并退款
                if ($refund['refund_status'] == 'SUCCESS') {
                    throw new ResourceException('退款单状态 已退款，无法继续操作');
                }
                if ($refund['refund_status'] == 'REFUSE') {
                    throw new ResourceException('退款单状态 已驳回，无法继续操作');
                }
                if ($refund['refund_status'] == 'AUDIT_SUCCESS') {
                    throw new ResourceException('退款单状态 已审核通过，无法继续操作');
                }
                if ($refund['refund_status'] == 'CANCEL') {
                    throw new ResourceException('退款单状态 已撤销，无法继续操作');
                }
                if ($refund['refund_status'] == 'PROCESSING') {
                    throw new ResourceException('退款单状态 已发起退款等待到账，无法继续操作');
                }
                if ($refund['refund_status'] == 'CHANGE') {
                    throw new ResourceException('退款单状态 退款异常，无法继续操作');
                }
                // 处理退款单状态
                $refundUpdate = [
                    'refund_status' => 'AUDIT_SUCCESS', // 审核成功待退款
                ];
                $refundFilter['refund_bn'] = $refund['refund_bn'];
                $aftersalesRefundService->updateOneBy($refundFilter, $refundUpdate);

                // 处理取消订单表状态
                $cancelOrderFilter = [
                    'order_id' => $params['order_id'],
                    'company_id' => $params['company_id'],
                ];
                $cancelOrderUpdate = [
                    'progress' => 2, // 处理中
                    'refund_status' => 'AUDIT_SUCCESS',
                ];
                $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
                $result = $cancelOrderRepository->updateOneBy($cancelOrderFilter, $cancelOrderUpdate);

                // 处理订单状态
                // 订单状态直接取消成功，退款实际是异步执行
                $updateInfo = [
                    'cancel_status' => 'SUCCESS',
                    'order_status' => 'CANCEL',
                ];
                $filter = [
                    'company_id' => $params['company_id'],
                    'order_id' => $params['order_id']
                ];
                $this->update($filter, $updateInfo);
                //退还积分
                // $orderEntity = $this->normalOrdersRepository->get($params['company_id'], $params['order_id']);
                // $orderData = $this->normalOrdersRepository->getServiceOrderData($orderEntity);
                // (new PointMemberService())->cancelOrderReturnBackPoints($orderData);
                $orderProcessLog = [
                    'order_id' => $params['order_id'],
                    'company_id' => $params['company_id'],
                    'operator_type' => $params['operator_type'] ?? 'system',
                    'operator_id' => $params['operator_id'] ?? 0,
                    'remarks' => '订单退款',
                    'detail' => '订单号：' . $params['order_id'] . '，后台管理员同意退款',
                    'params' => $params,
                ];
                event(new OrderProcessLogEvent($orderProcessLog));
            } else {
                if ($refund['refund_status'] != 'READY') {
                    throw new ResourceException('退款单状态不是待审核状态，无法拒绝');
                }
                // 处理退款单状态
                $refundUpdate = [
                    'refund_status' => 'REFUSE', // 审核拒绝
                ];
                $aftersalesRefundService->updateOneBy($refundFilter, $refundUpdate);

                $cancelOrderFilter = [
                    'order_id' => $params['order_id'],
                    'company_id' => $params['company_id'],
                ];
                $cancelOrderUpdate = [
                    'shop_reject_reason' => $params['shop_reject_reason'],
                    'progress' => 4, // 已拒绝
                    'refund_status' => 'SHOP_CHECK_FAILS', // 审核拒绝
                ];
                $cancelOrderRepository = app('registry')->getManager('default')->getRepository(CancelOrders::class);
                $result = $cancelOrderRepository->updateOneBy($cancelOrderFilter, $cancelOrderUpdate);
                $updateInfo = [
                    'cancel_status' => 'FAILS',
                ];
                $filter = [
                    'company_id' => $params['company_id'],
                    'order_id' => $params['order_id']
                ];
                $this->update($filter, $updateInfo);
                $orderProcessLog = [
                    'order_id' => $params['order_id'],
                    'company_id' => $params['company_id'],
                    'operator_type' => $params['operator_type'] ?? 'system',
                    'operator_id' => $params['operator_id'] ?? 0,
                    'remarks' => '订单退款',
                    'detail' => '订单号：' . $params['order_id'] . '，用户申请退款拒绝，拒绝原因：' . $params['shop_reject_reason'],
                    'params' => $params,
                ];
                event(new OrderProcessLogEvent($orderProcessLog));
            }
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 退还积分
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function backPoint($order)
    {
        $pointMemberService = new PointMemberService();
        $otherParams = ['point_type' => 'points_refund'];
        $pointMemberService->addPoint($order['user_id'], $order['company_id'], $order['point'], 9, true, '取消订单' . $order['order_id'] . '返还', $order['order_id'], $otherParams);
        return true;
    }

    /**
     * 更新销量
     * @param $orderId 订单id
     */
    public function incrSales($orderId, $companyId)
    {
        $list = $this->normalOrdersItemsRepository->getList(['order_id' => $orderId, 'company_id' => $companyId]);
        $itemsService = new ItemsService();
        foreach ($list['list'] as $v) {
            $itemsService->incrSales($v['item_id'], $v['num']);
        }
        return true;
    }

    public function formatOrderData($orderData, $params, $isCheck)
    {
        // 检查积分是否充足
        $pointMemberService = new PointMemberService();
        $pointMemberInfo = $pointMemberService->getInfo(['user_id' => $params['user_id'], 'company_id' => $params['company_id']]);
        if ($orderData['point'] <= 0) {
            if ($isCheck) {
                throw new ResourceException('订单使用积分不能低于一积分!');
            } else {
                $orderData['extraTips'] = '订单使用积分不能低于一积分';
            }
        }
        if (!isset($pointMemberInfo['point']) || $pointMemberInfo['point'] < $orderData['point']) {
            if ($isCheck) {
                throw new ResourceException('当前积分不足以支付本次订单费用!');
            } else {
                $orderData['extraTips'] = '当前积分不足以支付本次订单费用';
            }
        }
        return $orderData;
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->normalOrdersRepository->$method(...$parameters);
    }
}
