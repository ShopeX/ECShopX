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

namespace SupplierBundle\Services;

use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Events\PaySuccessEvent;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\TradeService;
use SupplierBundle\Entities\SupplierOrder;
use WechatBundle\Jobs\SendTemplateMessageJob;

class SupplierOrderService
{
    /**
     * @var \SupplierBundle\Repositories\SupplierOrderRepository
     */
    public $repository;

    public function __construct()
    {
        // KEY: U2hvcEV4
        $this->repository = app('registry')->getManager('default')->getRepository(SupplierOrder::class);
    }

    public function countOrderNum($filter)
    {
        // KEY: U2hvcEV4
        return $this->repository->count($filter);
    }


    //获取汇付的合单支付参数
    public function getSubOrders(&$payParams = [])
    {
        $sub_orders = [];
        $order_id = $payParams['order_id'];
        $company_id = $payParams['company_id'];
        $supplierOrders = $this->repository->getLists(['company_id' => $company_id, 'order_id' => $order_id]);
        if (!$supplierOrders) {
            return;
        }

        $supplierService = new SupplierService();
        $rs = $supplierService->repository->getLists(['operator_id' => array_column($supplierOrders, 'supplier_id')]);
        $supplierData = array_column($rs, null, 'operator_id');
        foreach ($supplierOrders as $v) {
            $sub_mch_id = $supplierData[$v['supplier_id']]['adapay_mch_id'] ?? '';
            if (!$sub_mch_id) {
                continue;
            }
            $sub_orders[] = [
                'sub_mch_id' => $sub_mch_id,
                'sub_order_no' => $v['order_id'],
                'amount' => bcdiv($v['total_fee'], '100', 2),
                'goods_name' => $v['title'],
                'goods_desc' => $v['title'],
                'order_desc' => $v['title'],
            ];
        }
        $payParams['business_mode'] = '01';
        $payParams['sub_orders'] = $sub_orders;
        return;
    }

    //获取订单状态的中文
    public function getOrderStatusMsg($order, $dadaData = null, $from = 'api')
    {
        $normalOrderService = new NormalOrderService();
        return $normalOrderService->getOrderStatusMsg($order, $dadaData, $from);
    }

    //筛选订单里的当前供应商的商品
    public function getSupplierOrderItems($authInfo, $orderInfo = [])
    {
        if ($authInfo['operator_type'] != 'supplier') {
            return $orderInfo;
        }

        $order_id = $orderInfo['orderInfo']['order_id'];
        $supplierId = $authInfo['operator_id'];
        foreach ($orderInfo['orderInfo']['items'] as $k => $v) {
            if ($v['supplier_id'] != $supplierId) {
                unset($orderInfo['orderInfo']['items'][$k]);
            }
        }
        $orderInfo['orderInfo']['items'] = array_values($orderInfo['orderInfo']['items']);

        $rs = $this->repository->getInfo(['supplier_id' => $supplierId, 'order_id' => $order_id]);
        if ($rs) {
            $orderInfo['orderInfo']['order_status'] = $rs['order_status'];
            $orderInfo['orderInfo']['delivery_status'] = $rs['delivery_status'];
            $orderInfo['orderInfo']['cancel_status'] = $rs['cancel_status'];
        }

        return $orderInfo;
    }

    // 未支付订单取消
    public function noPayOrderCancel(&$orderInfo = [])
    {
        $filter = [
            'company_id' => $orderInfo['company_id'],
            'order_id' => $orderInfo['order_id'],
            'order_status' => 'NOTPAY',
        ];
        if ($this->repository->count($filter)) {
            $updateInfo = [
                'order_status' => 'CANCEL',
                'cancel_status' => 'SUCCESS',
            ];
            $this->repository->updateBy($filter, $updateInfo);
        }
        return true;
    }

    /**
     * 供应商订单拆分
     */
    public function orderSplit($companyId, $orderId = '')
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->normalOrdersRepository->getInfo($filter);
        if ($orderInfo['distributor_id']) {
            // return true;//不处理店铺订单
        }

        $orderItems = $normalOrderService->normalOrdersItemsRepository->getList($filter);
        $supplierOrderItems = [];
        foreach ($orderItems['list'] as $item) {
            //supplier_id == 0的时候，表示是平台自营订单
            if (!$item['supplier_id']) continue;
            $supplierOrderItems[$item['supplier_id']][] = $item;
        }
        if (!$supplierOrderItems) return true;

        foreach ($supplierOrderItems as $supplierId => $items) {
            $supplierOrder = $orderInfo;
            $supplierOrder['supplier_id'] = $supplierId;
            $newTitle = $items[0]['item_name'];
            $newTotalfee = 0;
            $newItemFee = 0;
            $newCostFee = 0;
            $commission_fee = 0;//订单佣金
            $newMarketFee = 0;
            $newDiscountFee = 0;
            $newMemberDiscount = 0;
            $newCouponDiscount = 0;
            $newDiscountInfo = [];
            $newPoint = 0;
            $newItemPoint = 0;
            $newPointFee = 0;
            $newPointUse = 0;
            $newUppointUse = 0;
            $newGetPoints = 0;
            $newExtraPoints = 0;
            $newPointUpUse = 0;
            $newTaxableFee = 0;
            $newTotalTax = 0;
            $newLeftAftersalesNum = 0;
            foreach ($items as $item) {
                $newTotalfee += $item['total_fee'];
                $newItemFee += $item['item_fee'];
                $newCostFee += $item['cost_fee'];
                $commission_fee += $item['commission_fee'];
                $newMarketFee += $item['market_price'] * $item['num'];
                $newDiscountFee += $item['discount_fee'];
                $newMemberDiscount += $item['member_discount'];
                $newCouponDiscount += $item['coupon_discount'];
                $newDiscountInfo = array_merge($newDiscountInfo, $item['discount_info']);
                $newPoint += $item['point'];
                $newItemPoint += $item['item_point'] * $item['num'];
                $newPointFee += $item['point_fee'];
                $newPointUse += $item['point'];
                // $newUppointUse += $item['share_uppoints'];
                $newGetPoints += $item['get_points'];
                if ($supplierOrder['get_points'] > 0) {
                    $newExtraPoints += bcmul($item['get_points'], $supplierOrder['extra_points'] / $supplierOrder['get_points']);
                }
                if ($supplierOrder['point_up_use'] > 0 && $supplierOrder['uppoint_use'] > 0) {
                    $newPointUpUse = bcdiv($newUppointUse, $supplierOrder['uppoint_use'] / $supplierOrder['point_up_use']);
                }
                $newTaxableFee += ($item['taxable_fee'] ?? 0);
                $newTotalTax += ($item['cross_border_tax'] ?? 0);
                if ($supplierOrder['left_aftersales_num'] > 0) {
                    $newLeftAftersalesNum += $item['num'];
                }
            }

            //供应商订单运费
            $relData = $normalOrderService->normalOrdersRelSupplierRepository->getInfo(['order_id' => $supplierOrder['order_id'], 'supplier_id' => $supplierId]);

            $supplierOrder['freight_fee'] = $relData['freight_fee'] ?? 0;
            $supplierOrder['title'] = $newTitle;
            $supplierOrder['total_fee'] = $newTotalfee + $supplierOrder['freight_fee'];
            $supplierOrder['item_fee'] = $newItemFee;
            $supplierOrder['cost_fee'] = $newCostFee;
            $supplierOrder['commission_fee'] = $commission_fee;
            $supplierOrder['market_fee'] = $newMarketFee;
            $supplierOrder['discount_fee'] = $newDiscountFee;
            $supplierOrder['member_discount'] = $newMemberDiscount;
            $supplierOrder['coupon_discount'] = $newCouponDiscount;
            $supplierOrder['discount_info'] = is_array($newDiscountInfo) ? json_encode($newDiscountInfo, 256) : $newDiscountInfo;
            $supplierOrder['point'] = $newPoint;
            $supplierOrder['item_point'] = $newItemPoint;
            $supplierOrder['point_fee'] = $newPointFee;
            $supplierOrder['point_use'] = $newPointUse;
            $supplierOrder['uppoint_use'] = $newUppointUse;
            $supplierOrder['get_points'] = $newGetPoints;
            $supplierOrder['extra_points'] = $newExtraPoints;
            $supplierOrder['point_up_use'] = $newPointUpUse;
            $supplierOrder['taxable_fee'] = $newTaxableFee;
            $supplierOrder['total_tax'] = $newTotalTax;
            $supplierOrder['left_aftersales_num'] = $newLeftAftersalesNum;
            $supplierOrder['supplier_id'] = $supplierId;

            if ($this->repository->count(['supplier_id' => $supplierId, 'order_id' => $orderId])) {
                continue;//防止重复拆单
            }
            // app('log')->debug(var_export($supplierOrder, true));
            $this->repository->create($supplierOrder);
        }

        return true;
    }

    public function getOrderFilter($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'supplier_id' => $params['supplier_id'],
        ];

        $order_id = trim($params['order_id'] ?? '');
        $mobile = trim($params['mobile'] ?? '');
        $shop_name = trim($params['shop_name'] ?? '');
        $status = trim($params['order_status'] ?? '');
        $order_date = $params['order_date'] ?? [];

        if ($mobile) $filter['receiver_mobile'] = $mobile;
        if ($order_id) $filter['order_id'] = $order_id;
        if ($order_date) {
            $filter['create_time|gte'] = strtotime($order_date[0]);
            $filter['create_time|lte'] = strtotime($order_date[1]);
        }

        if ($status) {
            switch ($status) {
                case 'ordercancel':   //已取消待退款
                    $filter['order_status'] = 'CANCEL_WAIT_PROCESS';
                    $filter['cancel_status'] = 'WAIT_PROCESS';
                    break;
                case 'refundprocess':    //已取消待退款
                    $filter['order_status'] = 'CANCEL';
                    $filter['cancel_status'] = 'NO_APPLY_CANCEL';
                    break;
                case 'refundsuccess':    //已取消已退款
                    $filter['order_status'] = 'CANCEL';
                    $filter['cancel_status'] = 'SUCCESS';
                    break;
                case 'notship':  //待发货
                    $filter['order_status'] = 'PAYED';
                    $filter['cancel_status'] = ['NO_APPLY_CANCEL', 'FAILS'];
                    $filter['receipt_type'] = 'logistics';
                    break;
                case 'cancelapply':  //待退款
                    $filter['order_status'] = 'PAYED';
                    $filter['cancel_status'] = 'WAIT_PROCESS';
                    break;
                case 'ziti':  //待自提
                    $filter['receipt_type'] = 'ziti';
                    $filter['order_status'] = 'PAYED';
                    $filter['ziti_status'] = 'PENDING';
                    break;
                case 'shipping':  //带收货
                    $filter['order_status'] = 'WAIT_BUYER_CONFIRM';
                    $filter['delivery_status'] = ['DONE', 'PARTAIL'];
                    $filter['receipt_type'] = 'logistics';
                    break;
                case 'finish':  //已完成
                    $filter['order_status'] = 'DONE';
                    break;
                case 'reviewpass':  //待审核
                    $filter['order_status'] = 'REVIEW_PASS';
                    break;
                case 'done_noinvoice':  //已完成未开票
                    $filter['order_status'] = 'DONE';
                    $filter['invoice|neq'] = null;
                    $filter['is_invoiced'] = 0;
                    break;
                case 'done_invoice':  //已完成已开票
                    $filter['order_status'] = 'DONE';
                    $filter['invoice|neq'] = null;
                    $filter['is_invoiced'] = 1;
                    break;
                default:
                    $filter['order_status'] = strtoupper($status);
                    break;
            }
        }
        return $filter;
    }

    /**
     * 订单确认线下支付
     */
    public function orderPaidConfirm($companyId, $supplierId, $orderId)
    {
        $orderStatus = 'PAYED';
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = [
                'company_id' => $companyId,
                'supplier_id' => $supplierId,
                'order_id' => $orderId,
            ];
            $this->repository->updateOneBy($filter, ['order_status' => $orderStatus]);

            $filter = [
                'company_id' => $companyId,
                'order_id' => $orderId,
            ];
            $orders = $this->repository->getLists($filter);
            foreach ($orders as $order) {
                //只处理待支付的订单状态，其它状态的时候，保持原订单状态不变
                if (!in_array($order['order_status'], ['NOTPAY', 'PART_PAYMENT', 'PAYED'])) {
                    $orderStatus = $order['order_status'];
                    break;
                }
                if ($order['order_status'] != $orderStatus) {
                    $orderStatus = 'PART_PAYMENT';
                }
            }
            $normalOrderService = new NormalOrderService();
            $orderInfo = $normalOrderService->normalOrdersRepository->updateOneBy($filter, ['order_status' => $orderStatus]);

            $this->createOfflineTrade($companyId, $supplierId, $orderId);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }

        //写入订单日志
        $orderProcessLog = [
            'order_id' => $orderId,
            'company_id' => $companyId,
            'operator_type' => 'supplier',
            'operator_id' => $supplierId,
            'remarks' => trans('SupplierBundle.order_confirm_payment'),
            'detail' => trans('SupplierBundle.order_confirm_payment_detail', ['order_id' => $orderId]),
            'params' => [],
        ];
        event(new OrderProcessLogEvent($orderProcessLog));

        if ($orderInfo) {
            event(new PaySuccessEvent($orderInfo));
        }
    }

    /**
     * 创建线下支付的交易单
     * 外层有事务
     */
    public function createOfflineTrade($companyId, $supplierId, $orderId)
    {
        $filter = [
            'company_id' => $companyId,
            'supplier_id' => $supplierId,
            'order_id' => $orderId,
        ];
        $orderInfo = $this->repository->getInfo($filter);
        if (!$orderInfo) {
            throw new ResourceException(trans('SupplierBundle.order_not_exist'));
        }
        $data = [
            'company_id' => $orderInfo['company_id'],
            'user_id' => $orderInfo['user_id'],
            'total_fee' => $orderInfo['total_fee'],
            'detail' => $orderInfo['title'],
            'order_id' => $orderInfo['order_id'],
            'body' => $orderInfo['title'],
            'open_id' => $orderInfo['open_id'] ?? '',
            'wxa_appid' => $orderInfo['wxapp_appid'] ?? '',
            'mobile' => $orderInfo['receiver_mobile'],
            'pay_type' => $orderInfo['pay_type'],
            'pay_fee' => $orderInfo['total_fee'],
            'discount_fee' => $orderInfo['discount_fee'],
            'discount_info' => $orderInfo['discount_info'],
            'fee_rate' => $orderInfo['fee_rate'],
            'fee_type' => $orderInfo['fee_type'],
            'fee_symbol' => $orderInfo['fee_symbol'],
            'shop_id' => $orderInfo['shop_id'] ?? 0,
            'distributor_id' => $orderInfo['distributor_id'] ?? '',
            'trade_source_type' => $orderInfo['order_type'],
        ];
        $tradeService = new TradeService();
        $res = $tradeService->create($data);
        if ($res) {
            $data = $tradeService->tradeRepository->updateStatus($res['trade_id'], 'SUCCESS');
        }
        return $res;
    }

    /**
     * 处理供应商发货的商品
     */
    public function getDeliveryItems($params)
    {
        if ($params['delivery_type'] == 'sep') {
            return $params;
        }
        $sepInfo = [];
        $order_id = $params['order_id'];
        $supplier_id = $params['supplier_id'];
        $filter = [
            'order_id' => $order_id,
            'supplier_id' => $supplier_id,
        ];
        $orderInfo = $this->repository->getInfo($filter);

        $normalOrderService = new NormalOrderService();
        $orderItems = $normalOrderService->normalOrdersItemsRepository->getList($filter);
        foreach ($orderItems['list'] as $v) {
            $sepInfo[] = [
                'delivery_num' => $v['num'],
                'ship_num' => $v['num'],
                'delivery_item_num' => $v['delivery_item_num'],//已发货数量
                'num' => $v['num'],
                'id' => $v['id'],
                'item_id' => $v['item_id'],
                'item_name' => $v['item_name'],
                'pic' => $v['pic'],
                'delivery_corp' => $params['delivery_corp'],
                'delivery_code' => $params['delivery_code'],
                'delivery_status' => 'DONE',
            ];
        }

        $params['delivery_type'] = 'sep';
        $params['ship_mobile'] = $orderInfo['receiver_mobile'];
        $params['sepInfo'] = json_encode($sepInfo, 256);
        return $params;
    }

    /**
     * 更新供应商的订单付款状态
     */
    public function updateOrderStatus($filter, $updateStatus)
    {
        //暂时只处理订单完成和已付款状态。订单售后退款状态，需要每个供应商分开处理
        $statusConf = ['PAYED', 'DONE'];
        if (!in_array($updateStatus['order_status'], $statusConf)) {
            return true;
        }
        if (!$this->repository->count($filter)) {
            return true;
        }
        //如果是更新到已支付，只更新未支付和部分付款的订单
        if ($updateStatus['order_status'] == 'PAYED') {
            $filter['order_status'] = ['NOTPAY', 'PART_PAYMENT'];
        }
        $this->repository->updateBy($filter, $updateStatus);
        return true;
    }

    /**
     * 更新订单发货状态
     */
    public function updateShipStatus($params)
    {
        $order_id = $params['order_id'];
        $supplier_id = $params['supplier_id'];
        $updateInfo = [
            'delivery_status' => 'PENDING',
        ];

        $filter = [
            'order_id' => $order_id,
            'supplier_id' => $supplier_id,
        ];
        $normalOrderService = new NormalOrderService();
        $orderItems = $normalOrderService->normalOrdersItemsRepository->getList($filter);
        foreach ($orderItems['list'] as $v) {
            if ($v['delivery_item_num'] && $v['delivery_item_num'] >= $v['num']) {
                $updateInfo['delivery_status'] = 'DONE';
            } else {
                $updateInfo['delivery_status'] = 'PARTAIL';
                break;
            }
        }

        if ($updateInfo['delivery_status'] == 'DONE') {
            $updateInfo['order_status'] = 'WAIT_BUYER_CONFIRM';
        }

        $this->repository->updateOneBy($filter, $updateInfo);
    }

    public function setInvoiced($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'supplier_id' => $params['supplier_id'],
            'order_id' => $params['order_id'],
        ];
        $data = [
            'is_invoiced' => $params['status'] ? true : false
        ];
        $orderInfo = $this->repository->getInfo($filter);
        if (!$orderInfo or !$orderInfo['invoice']) {
            throw new ResourceException(trans('SupplierBundle.no_invoice_info'));
        }
        $ordersResult = $this->repository->updateOneBy($filter, $data);
        return $ordersResult;
    }


    /**
     * 订单确认收货
     */
    public function confirmReceipt($params)
    {
        $filter = [
            'company_id' => $params['company_id'],
            'order_id' => $params['order_id'],
        ];
        $updateInfo = [
            'order_status' => 'DONE',
            'end_time' => time(),
        ];
        if ($this->repository->count($filter)) {
            $this->repository->updateBy($filter, $updateInfo);
        }
    }

    //发送已支付的消息给供应商
    public function sendOfflinePaidMessage($companyId, $orderId, $userId)
    {
        //防止频繁发送
        $key = 'wxa_msg:' . $orderId;
        $redis = app('redis');
        if ($redis->get($key)) {
            return true;
        }
        $redis->set($key, 1, 'EX', 10);//10s内只发送一次

        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'user_id' => $userId,
        ];
        $supplierOrderService = new SupplierOrderService();
        $orders = $supplierOrderService->repository->getLists($filter);

        $supplierService = new SupplierService();
        $rs = $supplierService->repository->getLists(['operator_id' => array_column($orders, 'supplier_id')]);
        $supplierData = array_column($rs, null, 'operator_id');

        foreach ($orders as $v) {
            $wx_openid = $supplierData[$v['supplier_id']]['wx_openid'] ?? '';
            if (!$wx_openid) continue;
            $tousers = explode("\n", $wx_openid);
            // $tousers = ['obs5C6PehRYViUvYmyXiqu6VRSeU'];
            foreach ($tousers as $touser) {
                $msg_data = [
                    'number4' => $supplierData[$v['supplier_id']]['bank_account'] ?? trans('SupplierBundle.unknown_account_no'),
                    'time5' => date('Y-m-d H:i:s'),
                    'phrase2' => trans('SupplierBundle.offline_transfer'),//支付方式
                    'amount6' => bcdiv($v['total_fee'], '100', 2),
                    'const7' => trans('SupplierBundle.offline_payment'),//收款渠道
                ];
                $jobParams = [
                    'company_id' => $companyId,
                    'template_id' => 'ilBEvG-_TSx9i9XaYYDR8K-VW_UqG15z4MgOUx7LPjM',
                    'touser' => $touser,
                    'msg_data' => $msg_data,
                ];
                $gotoJob = (new SendTemplateMessageJob($jobParams))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            }
        }
    }

    //用户提交已付款申请
    public function buyerUpdatePayStatus($companyId, $orderId, $userId)
    {
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'user_id' => $userId,
        ];
        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->normalOrdersRepository->getInfo($filter);
        if (!$orderInfo) {
            throw new ResourceException(trans('SupplierBundle.order_not_exist_or_no_permission'));
        }
        if ($orderInfo['pay_type'] != 'offline') {
            throw new ResourceException(trans('SupplierBundle.order_not_offline_payment'));
        }
        if ($orderInfo['order_status'] != 'NOTPAY') {
            throw new ResourceException(trans('SupplierBundle.order_not_pending_payment'));
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'order_status' => 'NOTPAY',
            ];
            $data = [
                'order_status' => 'WAIT_PAID_CONFIRM',
            ];
            $normalOrderService->normalOrdersRepository->updateOneBy($filter, $data);

            $supplierOrderService = new SupplierOrderService();
            if ($supplierOrderService->repository->count($filter)) {
                $supplierOrderService->repository->updateBy($filter, $data);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException(trans('SupplierBundle.error_prefix') . $e->getMessage());
        }

        $this->sendOfflinePaidMessage($companyId, $orderId, $userId);

        return $orderInfo;
    }

    // 获取订单中供应商商品的数量
    public function getOrderSupplierNum($orderIds, $companyId, $supplier_id = 0)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb = $qb->select('order_id, sum(num) as num')
            ->from('orders_normal_orders_items')
            ->where($qb->expr()->eq('company_id', $companyId))
            ->andWhere($qb->expr()->in('order_id', $orderIds));
        if ($supplier_id > 0) {
            $qb = $qb->andWhere($qb->expr()->eq('supplier_id', $qb->expr()->literal($supplier_id)));
        }
        $qb = $qb->groupBy('order_id');
        $list = $qb->execute()->fetchAll();

        return $list;
    }

}
